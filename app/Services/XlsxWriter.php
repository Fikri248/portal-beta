<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

class XlsxWriter
{
    public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function generate(string $sheetName, array $headers, iterable $rows): string
    {
        $dataRows = is_array($rows) ? array_values($rows) : array_values(iterator_to_array($rows, false));
        $rowCount = max(1, count($dataRows) + 1);
        $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($headers)));
        $path = tempnam(sys_get_temp_dir(), 'tat-report-');

        if ($path === false) {
            throw new RuntimeException('File Excel laporan tidak dapat dibuat.');
        }

        $spreadsheet = null;

        try {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($sheetName);

            $this->writeRow($sheet, 1, $headers);

            foreach ($dataRows as $rowIndex => $row) {
                $this->writeRow($sheet, $rowIndex + 2, $row);
            }

            for ($column = 1; $column <= max(1, count($headers)); $column++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(22);
            }

            $sheet->getRowDimension(1)->setRowHeight(28);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$lastColumn.$rowCount);
            $sheet->getPageMargins()
                ->setLeft(0.25)
                ->setRight(0.25)
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setHeader(0.3)
                ->setFooter(0.3);

            $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray($this->headerStyle());

            if ($rowCount > 1) {
                $sheet->getStyle('A2:'.$lastColumn.$rowCount)->applyFromArray($this->dataStyle());
            }

            (new Xlsx($spreadsheet))->save($path);

            $content = file_get_contents($path);

            if ($content === false) {
                throw new RuntimeException('File Excel laporan tidak dapat dibaca.');
            }

            return $content;
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && $exception->getMessage() === 'File Excel laporan tidak dapat dibaca.') {
                throw $exception;
            }

            throw new RuntimeException('File Excel laporan tidak dapat dibuat.', 0, $exception);
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }

            @unlink($path);
        }
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function writeRow(Worksheet $sheet, int $rowNumber, array $row): void
    {
        foreach ($row as $columnIndex => $value) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$rowNumber;

            if ($value === null || $value === '') {
                $sheet->getCell($coordinate)->setValueExplicit(null, DataType::TYPE_NULL);

                continue;
            }

            $sheet->getCell($coordinate)->setValueExplicit((string) $value, DataType::TYPE_STRING);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function headerStyle(): array
    {
        return [
            'font' => [
                'name' => 'Aptos',
                'size' => 11,
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataStyle(): array
    {
        return [
            'font' => [
                'name' => 'Aptos',
                'size' => 11,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'numberFormat' => [
                'formatCode' => NumberFormat::FORMAT_TEXT,
            ],
        ];
    }
}
