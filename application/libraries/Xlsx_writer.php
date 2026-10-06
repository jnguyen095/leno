<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ghi file Excel .xlsx tối giản (1 sheet) bằng ZipArchive — không cần thư viện ngoài,
 * đi cặp với Xlsx_reader. Hỗ trợ: dòng tiêu đề in đậm + cố định + bộ lọc, ô chữ, ô số
 * (định dạng #,##0), ô ngày giờ (dd/mm/yyyy hh:mm), công thức, độ rộng cột.
 *
 *   $this->load->library('xlsx_writer');
 *   $x = $this->xlsx_writer;
 *   $x->set_columns(array('Mã đơn' => 18, 'Tổng tiền' => 14));
 *   $x->add_row(array('ORD-1', array('n' => 30000)));
 *   $x->download('don_hang.xlsx');
 *
 * Kiểu ô trong add_row(): chuỗi/NULL = chữ; array('n' => số); array('d' => 'Y-m-d H:i:s');
 * array('f' => 'SUM(A2:A9)') = công thức (hiển thị kiểu số); thêm 'b' => TRUE để in đậm.
 */
class Xlsx_writer
{
    const STYLE_TEXT = 0;
    const STYLE_HEADER = 1;
    const STYLE_NUMBER = 2;
    const STYLE_DATE = 3;
    const STYLE_BOLD = 4;
    const STYLE_BOLD_NUMBER = 5;

    protected $headers = array();
    protected $widths = array();
    protected $rows = array();
    protected $sheet_name = 'Sheet1';
    protected $filter_rows = NULL;   // số dòng dữ liệu nằm trong bộ lọc (NULL = tất cả)

    /** Tên cột => độ rộng (ký tự). Tạo luôn dòng tiêu đề. */
    public function set_columns(array $columns)
    {
        $this->headers = array_keys($columns);
        $this->widths = array_values($columns);
        return $this;
    }

    public function set_sheet_name($name)
    {
        // Excel: tối đa 31 ký tự, không chứa : \ / ? * [ ]
        $this->sheet_name = mb_substr(preg_replace('/[:\\\\\/\?\*\[\]]/', ' ', $name), 0, 31, 'UTF-8');
        return $this;
    }

    public function add_row(array $cells)
    {
        $this->rows[] = array_values($cells);
        return $this;
    }

    /** Số dòng dữ liệu đã thêm (không tính dòng tiêu đề) — dùng để viết công thức tổng. */
    public function row_count()
    {
        return count($this->rows);
    }

    /** Gọi trước khi thêm dòng tổng: bộ lọc (AutoFilter) chỉ phủ các dòng dữ liệu đã thêm tới đây. */
    public function end_data()
    {
        $this->filter_rows = count($this->rows);
        return $this;
    }

    /** Chữ cái cột theo chỉ số 0 (0 -> A, 26 -> AA). */
    public static function col_letter($index)
    {
        $letter = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26))
        {
            $letter = chr(65 + ($n - 1) % 26).$letter;
        }
        return $letter;
    }

    /** Gửi file về trình duyệt để tải xuống rồi dừng. */
    public function download($filename)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->save($tmp);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.str_replace('"', '', $filename).'"');
        header('Content-Length: '.filesize($tmp));
        header('Cache-Control: max-age=0');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function save($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE)
        {
            show_error('Không tạo được file Excel.');
        }
        $zip->addFromString('[Content_Types].xml', $this->_content_types());
        $zip->addFromString('_rels/.rels', $this->_root_rels());
        $zip->addFromString('xl/workbook.xml', $this->_workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->_workbook_rels());
        $zip->addFromString('xl/styles.xml', $this->_styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->_sheet());
        $zip->close();
    }

    protected function _sheet()
    {
        $last_col = self::col_letter(max(0, count($this->headers) - 1));
        $last_row = ($this->filter_rows === NULL ? count($this->rows) : $this->filter_rows) + 1;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>';

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
        $xml .= $this->_row_xml(1, $this->headers, TRUE);
        foreach ($this->rows as $i => $cells)
        {
            $xml .= $this->_row_xml($i + 2, $cells, FALSE);
        }
        $xml .= '</sheetData>';

        if ($this->headers)
        {
            $xml .= '<autoFilter ref="A1:'.$last_col.$last_row.'"/>';
        }
        $xml .= '<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';
        return $xml;
    }

    protected function _row_xml($r, array $cells, $is_header)
    {
        $xml = '<row r="'.$r.'">';
        foreach ($cells as $c => $cell)
        {
            $ref = self::col_letter($c).$r;
            if ($is_header)
            {
                $xml .= $this->_text_cell($ref, $cell, self::STYLE_HEADER);
                continue;
            }

            $bold = is_array($cell) && ! empty($cell['b']);
            if (is_array($cell) && array_key_exists('n', $cell))
            {
                if ($cell['n'] === NULL || $cell['n'] === '') { $xml .= '<c r="'.$ref.'"/>'; continue; }
                $xml .= '<c r="'.$ref.'" s="'.($bold ? self::STYLE_BOLD_NUMBER : self::STYLE_NUMBER).'"><v>'.(0 + $cell['n']).'</v></c>';
            }
            elseif (is_array($cell) && array_key_exists('d', $cell))
            {
                $ts = $cell['d'] ? strtotime($cell['d']) : FALSE;
                if ($ts === FALSE) { $xml .= '<c r="'.$ref.'"/>'; continue; }
                // Ngày giờ Excel = số ngày kể từ 1899-12-30 theo giờ địa phương.
                $serial = ($ts + (int) date('Z', $ts)) / 86400 + 25569;
                $xml .= '<c r="'.$ref.'" s="'.self::STYLE_DATE.'"><v>'.round($serial, 6).'</v></c>';
            }
            elseif (is_array($cell) && array_key_exists('f', $cell))
            {
                $xml .= '<c r="'.$ref.'" s="'.($bold ? self::STYLE_BOLD_NUMBER : self::STYLE_NUMBER).'"><f>'.$this->_esc($cell['f']).'</f></c>';
            }
            else
            {
                $text = is_array($cell) ? (isset($cell['t']) ? $cell['t'] : '') : $cell;
                $xml .= $this->_text_cell($ref, $text, $bold ? self::STYLE_BOLD : self::STYLE_TEXT);
            }
        }
        return $xml.'</row>';
    }

    protected function _text_cell($ref, $text, $style)
    {
        if ($text === NULL || $text === '')
        {
            return '<c r="'.$ref.'" s="'.$style.'"/>';
        }
        return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->_esc($text).'</t></is></c>';
    }

    protected function _esc($s)
    {
        // Bỏ ký tự điều khiển không hợp lệ trong XML rồi escape.
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $s);
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    protected function _styles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy hh:mm"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE9ECEF"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left/><right/><top/><bottom style="thin"><color rgb="FFADB5BD"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                         // 0 chữ
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' // 1 tiêu đề
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                    // 2 số #,##0
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                  // 3 ngày giờ
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                            // 4 chữ đậm
            .'<xf numFmtId="3" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'      // 5 số đậm
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    protected function _content_types()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    protected function _root_rels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    protected function _workbook()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->_esc($this->sheet_name).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    protected function _workbook_rels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }
}
