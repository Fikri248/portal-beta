<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class XlsxReader
{
    /**
     * @return array<int, array<int, scalar|null>>
     */
    public function read(string $path): array
    {
        if (! extension_loaded('zip')) {
            throw new RuntimeException('Ekstensi ZIP PHP tidak tersedia.');
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File Excel tidak dapat dibuka.');
        }

        $spreadsheet = null;

        try {
            $reader = new Xlsx;
            $reader->setReadDataOnly(false);
            $reader->setReadEmptyCells(true);
            $reader->setIgnoreRowsWithNoCells(false);

            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getSheet(0);
            $rows = $this->readRows($worksheet);

            $this->applyMergedCells($worksheet, $rows);

            ksort($rows);

            foreach ($rows as &$row) {
                ksort($row);
            }
            unset($row);

            return $rows;
        } catch (Throwable $exception) {
            throw new RuntimeException('File Excel tidak dapat dibuka.', 0, $exception);
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function readRows(Worksheet $worksheet): array
    {
        $rows = [];

        foreach (array_keys($worksheet->getRowDimensions()) as $rowNumber) {
            $rows[(int) $rowNumber] ??= [];
        }

        foreach ($worksheet->getCellCollection()->getCoordinates() as $coordinate) {
            $cell = $worksheet->getCell($coordinate);
            $rowNumber = $cell->getRow();
            $columnNumber = Coordinate::columnIndexFromString($cell->getColumn());

            $rows[$rowNumber] ??= [];
            $rows[$rowNumber][$columnNumber] = $this->readCellValue($cell);
        }

        return $rows;
    }

    private function readCellValue(Cell $cell): ?string
    {
        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
            return $this->normalizeValue($cell->getOldCalculatedValue());
        }

        $value = $cell->getValue();

        if ($value === null) {
            return null;
        }

        if ($cell->getDataType() === DataType::TYPE_BOOL) {
            return $value ? '1' : '0';
        }

        if ($value instanceof RichText) {
            return $value->getPlainText();
        }

        return $this->normalizeValue($value);
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    private function applyMergedCells(Worksheet $worksheet, array &$rows): void
    {
        foreach ($worksheet->getMergeCells() as $reference) {
            [$start, $end] = Coordinate::rangeBoundaries($reference);
            [$startColumn, $startRow] = $start;
            [$endColumn, $endRow] = $end;
            $value = $rows[$startRow][$startColumn] ?? null;

            for ($row = $startRow; $row <= $endRow; $row++) {
                for ($column = $startColumn; $column <= $endColumn; $column++) {
                    $rows[$row] ??= [];
                    $rows[$row][$column] ??= $value;
                }
            }
        }
    }
}
