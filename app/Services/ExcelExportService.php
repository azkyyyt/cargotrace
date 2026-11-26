<?php

namespace App\Services;

use ZipArchive;

/**
 * Lightweight native OpenXML (.xlsx) Spreadsheet Builder.
 * Produces genuine Microsoft Excel workbooks with zero external dependencies.
 */
class ExcelExportService
{
    /**
     * Build a native .xlsx file at a temporary path and return the file path.
     *
     * @param string $sheetName
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     * @param array<int, mixed>|null $totalsRow
     * @return string
     */
    public function generateXlsx(string $sheetName, array $headers, array $rows, ?array $totalsRow = null): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'cargotrace_xlsx_') . '.xlsx';

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to initialize XLSX export archive.');
        }

        $safeSheetTitle = substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $sheetName), 0, 31) ?: 'FinancialReport';

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($safeSheetTitle));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($headers, $rows, $totalsRow));

        $zip->close();

        return $tmpPath;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $num = $index + 1;
        while ($num > 0) {
            $rem = ($num - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $num = intdiv($num - 1, 26);
        }
        return $letter;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function buildCellXml(int $rowIdx, int $colIdx, mixed $val, int $styleIndex = 0): string
    {
        $ref = $this->columnLetter($colIdx) . $rowIdx;

        if (is_int($val) || is_float($val)) {
            return "<c r=\"{$ref}\" s=\"{$styleIndex}\"><v>{$val}</v></c>";
        }

        $text = $this->xmlEscape((string) $val);
        return "<c r=\"{$ref}\" t=\"inlineStr\" s=\"{$styleIndex}\"><is><t>{$text}</t></is></c>";
    }

    private function worksheetXml(array $headers, array $rows, ?array $totalsRow): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<cols>';
        for ($i = 0; $i < count($headers); $i++) {
            $colNum = $i + 1;
            $xml .= "<col min=\"{$colNum}\" max=\"{$colNum}\" width=\"20\" customWidth=\"1\"/>";
        }
        $xml .= '</cols>';
        $xml .= '<sheetData>';

        $rowNum = 1;

        // Header Row (Style 1 = Bold Slate Header)
        $xml .= "<row r=\"{$rowNum}\">";
        foreach ($headers as $cIdx => $header) {
            $xml .= $this->buildCellXml($rowNum, $cIdx, (string) $header, 1);
        }
        $xml .= '</row>';
        $rowNum++;

        // Data Rows
        foreach ($rows as $row) {
            $xml .= "<row r=\"{$rowNum}\">";
            foreach ($row as $cIdx => $val) {
                $style = (is_int($val) || is_float($val)) ? 2 : 0;
                $xml .= $this->buildCellXml($rowNum, $cIdx, $val, $style);
            }
            $xml .= '</row>';
            $rowNum++;
        }

        // Totals Row
        if ($totalsRow !== null) {
            $xml .= "<row r=\"{$rowNum}\">";
            foreach ($totalsRow as $cIdx => $val) {
                $style = (is_int($val) || is_float($val)) ? 3 : 1;
                $xml .= $this->buildCellXml($rowNum, $cIdx, $val, $style);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <numFmts count="1">
        <numFmt numFmtId="164" formatCode="$#,##0.00"/>
    </numFmts>
    <fonts count="2">
        <font><sz val="10"/><name val="Calibri"/></font>
        <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="4">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFCBD5E1"/></left>
            <right style="thin"><color rgb="FFCBD5E1"/></right>
            <top style="thin"><color rgb="FFCBD5E1"/></top>
            <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="4">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1"/>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/>
        <xf numFmtId="164" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1"/>
    </cellXfs>
</styleSheet>
XML;
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function rootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function workbookXml(string $sheetTitle): string
    {
        $title = $this->xmlEscape($sheetTitle);
        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="{$title}" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML;
    }
}
