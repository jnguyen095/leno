<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Đọc file .xlsx (native, không phụ thuộc PhpSpreadsheet/Composer — project
 * này không dùng Composer) hoặc .csv, trả về mảng các dòng dữ liệu.
 *
 * .xlsx thực chất là 1 file ZIP chứa các file XML (OOXML). Ta chỉ cần đọc
 * xl/sharedStrings.xml (bảng chuỗi dùng chung) + xl/worksheets/sheet1.xml
 * (sheet đầu tiên) bằng ZipArchive + SimpleXML — cả 2 extension đều có sẵn
 * trong PHP mặc định.
 *
 * Dòng đầu tiên trả về được coi là dòng tiêu đề (header) bởi code gọi.
 */
class Xlsx_reader
{
    /**
     * @param string $path đường dẫn file đã upload
     * @return array mảng các dòng, mỗi dòng là mảng chuỗi theo cột (0-based)
     * @throws Exception nếu không đọc được file
     */
    public function read($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv')
        {
            return $this->_read_csv($path);
        }
        if ($ext === 'xlsx')
        {
            return $this->_read_xlsx($path);
        }

        throw new Exception('Chỉ hỗ trợ file .xlsx hoặc .csv.');
    }

    private function _read_csv($path)
    {
        $rows = array();
        $handle = fopen($path, 'r');
        if ($handle === FALSE)
        {
            throw new Exception('Không mở được file CSV.');
        }

        // BOM UTF-8 do Excel thường chèn vào đầu file CSV.
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF")
        {
            rewind($handle);
        }

        $first_line = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF")
        {
            fseek($handle, 3);
        }
        $delimiter = (substr_count($first_line, ';') > substr_count($first_line, ',')) ? ';' : ',';

        while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE)
        {
            $rows[] = array_map('trim', $row);
        }
        fclose($handle);

        return $rows;
    }

    private function _read_xlsx($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE)
        {
            throw new Exception('Không đọc được file .xlsx (file có thể bị lỗi).');
        }

        $shared_strings = $this->_read_shared_strings($zip);

        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet_xml === FALSE)
        {
            throw new Exception('File .xlsx không có sheet dữ liệu.');
        }

        $xml = simplexml_load_string($sheet_xml);
        if ($xml === FALSE)
        {
            throw new Exception('Không đọc được nội dung file .xlsx.');
        }

        $rows = array();
        foreach ($xml->sheetData->row as $row_xml)
        {
            $row = array();
            foreach ($row_xml->c as $cell)
            {
                $ref = (string) $cell['r'];
                $col_index = $this->_col_index($ref);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's')
                {
                    $idx = (int) $cell->v;
                    $value = isset($shared_strings[$idx]) ? $shared_strings[$idx] : '';
                }
                elseif ($type === 'inlineStr')
                {
                    $value = (string) $cell->is->t;
                }
                else
                {
                    $value = (string) $cell->v;
                }

                $row[$col_index] = trim($value);
            }

            if (empty($row))
            {
                continue;
            }

            // Điền ô trống cho các cột bị bỏ qua để giữ đúng vị trí cột.
            $max_col = max(array_keys($row));
            $full_row = array();
            for ($i = 0; $i <= $max_col; $i++)
            {
                $full_row[] = isset($row[$i]) ? $row[$i] : '';
            }
            $rows[] = $full_row;
        }

        return $rows;
    }

    private function _read_shared_strings($zip)
    {
        $xml_str = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml_str === FALSE)
        {
            return array();
        }

        $xml = simplexml_load_string($xml_str);
        $strings = array();
        foreach ($xml->si as $si)
        {
            if (isset($si->t))
            {
                $strings[] = (string) $si->t;
            }
            else
            {
                // Rich text: nhiều <r><t>...</t></r> ghép lại thành 1 chuỗi.
                $text = '';
                foreach ($si->r as $r)
                {
                    $text .= (string) $r->t;
                }
                $strings[] = $text;
            }
        }

        return $strings;
    }

    /** Chuyển ô tham chiếu kiểu "B5" thành chỉ số cột 0-based (B -> 1). */
    private function _col_index($ref)
    {
        $letters = preg_replace('/[0-9]/', '', $ref);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++)
        {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return max(0, $index - 1);
    }
}
