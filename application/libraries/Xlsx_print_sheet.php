<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'libraries/Xlsx_writer.php';

/**
 * Trang Excel để IN (dán tường...): khác Xlsx_writer (bảng dữ liệu có bộ lọc), sheet này có tiêu đề lớn, kẻ ô toàn bộ,
 * chữ to + tự xuống dòng, gộp ô (merge), khổ A4 dọc vừa 1 trang ngang, dòng tiêu đề cột lặp lại ở mỗi trang in.
 * Dùng lại phần đóng gói file của Xlsx_writer.
 *
 *   $this->load->library('xlsx_print_sheet');
 *   $s = $this->xlsx_print_sheet;
 *   $s->set_widths(array(24, 46, 18));
 *   $s->add('Công thức pha chế', 'title', 30, 3);   // 1 ô gộp ngang 3 cột
 *   $s->add(array('Món', 'Thành phần', 'Khối lượng'), 'header', 24)->repeat_rows(2, 2);
 *   $s->add(array('Kem muối', 'Sữa tươi', '200 ml'), array('name', 'cell', 'qty'));
 *   $s->merge('A3:A9');
 *   $s->download('cong_thuc.xlsx');
 *
 * Kiểu ô: title (tiêu đề lớn), subtitle (dòng phụ nghiêng), header (tiêu đề cột), name (tên món — đậm, giữa dọc),
 * cell (chữ thường), qty (căn giữa, đậm), note (ghi chú nghiêng). Mọi kiểu trừ title/subtitle đều kẻ ô.
 */
class Xlsx_print_sheet extends Xlsx_writer
{
    const STYLES = array('text' => 0, 'title' => 1, 'subtitle' => 2, 'header' => 3, 'name' => 4, 'cell' => 5, 'qty' => 6, 'note' => 7, 'empty' => 8);

    protected $print_rows = array();   // [[cells], [styles], height]
    protected $merges = array();
    protected $repeat = NULL;          // [dòng đầu, dòng cuối] lặp lại ở mỗi trang in
    protected $breaks = array();       // ngắt trang thủ công (sau dòng id)

    public function set_widths(array $widths)
    {
        $this->widths = array_values($widths);
        return $this;
    }

    /**
     * Thêm 1 dòng. $cells: mảng ô (hoặc 1 chuỗi); $styles: 1 kiểu cho cả dòng hoặc mảng theo từng ô;
     * $height: chiều cao dòng (pt, NULL = tự động); $span: gộp ngang từ cột A qua $span cột (dòng tiêu đề).
     */
    public function add($cells, $styles = 'cell', $height = NULL, $span = 1)
    {
        $cells = is_array($cells) ? array_values($cells) : array($cells);
        $styles = is_array($styles) ? array_values($styles) : array_fill(0, max(count($cells), $span), $styles);
        // Ô gộp ngang: thêm ô trống cùng kiểu để viền vẽ đủ.
        while (count($cells) < $span) $cells[] = '';
        while (count($styles) < count($cells)) $styles[] = end($styles);
        $this->print_rows[] = array($cells, $styles, $height);
        $r = count($this->print_rows);
        if ($span > 1) $this->merge('A'.$r.':'.self::col_letter($span - 1).$r);
        return $this;
    }

    /** Số dòng hiện có (dòng vừa thêm = row_count()). */
    public function row_count()
    {
        return count($this->print_rows);
    }

    /** Chiều cao dòng $row (pt; dòng không đặt chiều cao tính 20). */
    public function row_height($row)
    {
        return isset($this->print_rows[$row - 1]) ? (float) ($this->print_rows[$row - 1][2] ?: 20) : 0.0;
    }

    /** Sang trang mới ngay trước dòng $row khi in. */
    public function page_break_before($row)
    {
        if ($row > 1) $this->breaks[] = $row - 1; // OOXML: ngắt sau dòng id
        return $this;
    }

    /** Tăng chiều cao dòng $row (1 = dòng đầu) thêm $extra pt. */
    public function add_height($row, $extra)
    {
        $i = $row - 1;
        if (isset($this->print_rows[$i])) $this->print_rows[$i][2] = ($this->print_rows[$i][2] ?: 20) + $extra;
        return $this;
    }

    /** Gộp ô, vd 'A3:A9'. */
    public function merge($range)
    {
        $this->merges[] = $range;
        return $this;
    }

    /** Lặp lại các dòng $from..$to ở đầu mỗi trang in (dòng tiêu đề cột). */
    public function repeat_rows($from, $to)
    {
        $this->repeat = array((int) $from, (int) $to);
        return $this;
    }

    protected function _sheet()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<sheetViews><sheetView workbookViewId="0" view="pageLayout"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/>';
        if ($this->widths)
        {
            $xml .= '<cols>';
            foreach ($this->widths as $i => $w)
            {
                $xml .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.(float) $w.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($this->print_rows as $i => $row)
        {
            list($cells, $styles, $height) = $row;
            $r = $i + 1;
            $xml .= '<row r="'.$r.'"'.($height ? ' ht="'.(float) $height.'" customHeight="1"' : '').'>';
            foreach ($cells as $c => $text)
            {
                $style = isset(self::STYLES[$styles[$c]]) ? self::STYLES[$styles[$c]] : 0;
                $xml .= $this->_text_cell(self::col_letter($c).$r, $text, $style);
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if ($this->merges)
        {
            $xml .= '<mergeCells count="'.count($this->merges).'">';
            foreach ($this->merges as $m) $xml .= '<mergeCell ref="'.$m.'"/>';
            $xml .= '</mergeCells>';
        }
        // In: lề hẹp, căn giữa ngang, A4 dọc, vừa 1 trang theo chiều ngang (chiều dọc tự sang trang).
        $xml .= '<printOptions horizontalCentered="1"/>'
            .'<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            .'<pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/>'
            .'<headerFooter><oddFooter>&amp;C&amp;9Trang &amp;P / &amp;N</oddFooter></headerFooter>'
            .($this->breaks ? '<rowBreaks count="'.count($this->breaks).'" manualBreakCount="'.count($this->breaks).'">'
                .implode('', array_map(function ($id) { return '<brk id="'.(int) $id.'" max="16383" man="1"/>'; }, array_unique($this->breaks))).'</rowBreaks>' : '')
            .'</worksheet>';
        return $xml;
    }

    protected function _workbook()
    {
        $defined = '';
        if ($this->repeat)
        {
            // Dòng tiêu đề cột lặp lại ở mỗi trang in.
            $defined = '<definedNames><definedName name="_xlnm.Print_Titles" localSheetId="0">\''
                .$this->_esc(str_replace("'", "''", $this->sheet_name)).'\'!$'.$this->repeat[0].':$'.$this->repeat[1].'</definedName></definedNames>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->_esc($this->sheet_name).'" sheetId="1" r:id="rId1"/></sheets>'
            .$defined
            .'</workbook>';
    }

    protected function _styles()
    {
        $b = '<border><left style="thin"><color rgb="FF495057"/></left><right style="thin"><color rgb="FF495057"/></right>'
            .'<top style="thin"><color rgb="FF495057"/></top><bottom style="thin"><color rgb="FF495057"/></bottom><diagonal/></border>';
        $wrap = '<alignment vertical="center" wrapText="1" indent="1"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="6">'
            .'<font><sz val="13"/><name val="Arial"/></font>'                    // 0 thường
            .'<font><b/><sz val="20"/><name val="Arial"/></font>'                // 1 tiêu đề
            .'<font><i/><sz val="11"/><color rgb="FF6C757D"/><name val="Arial"/></font>' // 2 dòng phụ
            .'<font><b/><sz val="13"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>' // 3 tiêu đề cột
            .'<font><b/><sz val="14"/><name val="Arial"/></font>'                // 4 tên món / đậm
            .'<font><i/><sz val="11"/><name val="Arial"/></font>'                // 5 ghi chú
            .'</fonts>'
            .'<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF343A40"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFF8E1"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'.$b.'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="9">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                                  // 0 text
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 1 title
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 2 subtitle
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 3 header
            .'<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 4 name
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'.$wrap.'</xf>'                // 5 cell
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 6 qty
            .'<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1" indent="1"/></xf>' // 7 note
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'                                                // 8 empty (kẻ ô)
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
