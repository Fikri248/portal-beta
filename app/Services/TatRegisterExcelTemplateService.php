<?php

namespace App\Services;

use App\Models\NarcoticType;
use App\Models\Unit;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

class TatRegisterExcelTemplateService
{
    public const FILENAME = 'Template_Import_Register_TAT.xlsx';

    public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const BEFORE_RECOMMENDATION_HEADERS = [
        'NO',
        'NO/BLN',
        'ASAL PENGAJUAN',
        'TANGGAL SURAT',
        'TANGGAL BERKAS DITERIMA',
        'TANGGAL PELAKSANAAN',
        'NO SURAT PENGAJUAN',
        'NO LKN/LP/LI',
        'TGL PENANGKAPAN',
        'NAMA',
        'NO REGISTER',
        'ALAMAT KTP',
        'ALAMAT DOMISILI',
        'TEMPAT LAHIR',
        'TANGGAL LAHIR',
        'JENIS KELAMIN',
        'USIA',
        'PENDIDIKAN',
        'PEKERJAAN',
        'NO HANDPHONE',
        'NIK',
        'JENIS NARKOTIKA',
        'JUMLAH / BERAT',
        'SATUAN',
        'PASAL YANG DISANGKAKAN',
        'HASIL ASESMEN HUKUM',
        'HASIL ASESMEN MEDIS',
    ];

    private const RECOMMENDATION_HEADERS = [
        'RAWAT JALAN',
        'RAWAT INAP',
        'REHAB DI LAPAS/RUTAN',
        'TIDAK REHAB (PROSES HUKUM)',
    ];

    private const AFTER_RECOMMENDATION_HEADERS = [
        'PELAKSANAAN REKOMENDASI',
        'HASIL REKOM TAT',
        'KET',
    ];

    private const FIRST_DATA_ROW = 3;

    private const LAST_DATA_ROW = 102;

    /**
     * @return string XLSX file contents
     */
    public function generate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tat-template-');

        if ($path === false) {
            throw new RuntimeException('Template Excel tidak dapat dibuat.');
        }

        $spreadsheet = null;

        try {
            $spreadsheet = $this->buildSpreadsheet();
            (new Xlsx($spreadsheet))->save($path);

            $content = file_get_contents($path);

            if ($content === false) {
                throw new RuntimeException('Template Excel tidak dapat dibaca.');
            }

            return $content;
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && $exception->getMessage() === 'Template Excel tidak dapat dibaca.') {
                throw $exception;
            }

            throw new RuntimeException('Template Excel tidak dapat dibuat.', 0, $exception);
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }

            @unlink($path);
        }
    }

    private function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $registerSheet = $spreadsheet->getActiveSheet();
        $registerSheet->setTitle('Register TAT');

        $this->buildRegisterSheet($registerSheet);

        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Petunjuk');
        $this->buildInstructionSheet($instructionSheet);

        return $spreadsheet;
    }

    private function buildRegisterSheet(Worksheet $sheet): void
    {
        $before = self::BEFORE_RECOMMENDATION_HEADERS;
        $recommendations = self::RECOMMENDATION_HEADERS;
        $after = self::AFTER_RECOMMENDATION_HEADERS;
        $allHeaders = array_merge($before, $recommendations, $after);
        $recommendationStart = count($before) + 1;
        $recommendationEnd = $recommendationStart + count($recommendations) - 1;
        $lastColumn = Coordinate::stringFromColumnIndex(count($allHeaders));

        for ($column = 1; $column <= count($allHeaders); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($letter)->setWidth(18);
        }

        foreach ($before as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
        }

        $sheet->setCellValue(Coordinate::stringFromColumnIndex($recommendationStart).'1', 'REKOMENDASI TAT');

        foreach ($recommendations as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($recommendationStart + $index).'2', $header);
        }

        foreach ($after as $index => $header) {
            $column = $recommendationEnd + $index + 1;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($column).'1', $header);
        }

        for ($column = 1; $column <= count($allHeaders); $column++) {
            if ($column >= $recommendationStart && $column <= $recommendationEnd) {
                continue;
            }

            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->mergeCells($letter.'1:'.$letter.'2');
        }

        $sheet->mergeCells(
            Coordinate::stringFromColumnIndex($recommendationStart).'1:'
            .Coordinate::stringFromColumnIndex($recommendationEnd).'1'
        );

        $sheet->freezePane('A3');
        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getRowDimension(2)->setRowHeight(36);

        $sheet->getStyle('A1:'.$lastColumn.'2')->applyFromArray($this->headerStyle());
        $sheet->getStyle('A'.$this::FIRST_DATA_ROW.':'.$lastColumn.$this::LAST_DATA_ROW)
            ->applyFromArray($this->inputStyle());

        foreach ([4, 5, 6, 9, 15] as $column) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getStyle($letter.self::FIRST_DATA_ROW.':'.$letter.self::LAST_DATA_ROW)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }

        $quantityColumn = Coordinate::stringFromColumnIndex(23);
        $sheet->getStyle($quantityColumn.self::FIRST_DATA_ROW.':'.$quantityColumn.self::LAST_DATA_ROW)
            ->getNumberFormat()
            ->setFormatCode('0.000');
    }

    private function buildInstructionSheet(Worksheet $sheet): void
    {
        $narcotics = NarcoticType::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();
        $units = Unit::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();
        $rows = [
            ['Template Import Register TAT', ''],
            ['Tanggal', 'Gunakan format DD/MM/YYYY.'],
            ['Rekomendasi TAT', 'Isi X tepat satu pilihan pada kolom RAWAT JALAN, RAWAT INAP, REHAB DI LAPAS/RUTAN, atau TIDAK REHAB (PROSES HUKUM).'],
            ['Jenis Kelamin', 'L atau P'],
            ['Pelaksanaan Rekomendasi', 'DILAKSANAKAN atau TIDAK DILAKSANAKAN'],
            ['Satuan aktif', $units ? implode(', ', $units) : 'Belum ada master satuan aktif.'],
            ['Jenis narkotika aktif', $narcotics ? implode('; ', $narcotics) : 'Belum ada master jenis narkotika aktif.'],
            ['Batasan import', 'Satu baris Excel saat ini mendukung paling banyak satu jenis narkotika. Jangan menggabungkan beberapa jenis narkotika dalam satu sel.'],
            ['Kolom teks', 'NIK, nomor telepon, nomor register, nomor surat, dan nomor LKN/LP/LI diformat sebagai teks untuk menjaga angka nol di depan.'],
        ];

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(100);
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($rows as $rowIndex => [$label, $value]) {
            $rowNumber = $rowIndex + 1;
            $sheet->setCellValue('A'.$rowNumber, $label);
            $sheet->setCellValue('B'.$rowNumber, $value);
        }

        $sheet->getStyle('A1:B1')->applyFromArray($this->headerStyle());
        $sheet->getStyle('A2:B'.count($rows))->applyFromArray($this->inputStyle());
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
    private function inputStyle(): array
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
            'numberFormat' => [
                'formatCode' => NumberFormat::FORMAT_TEXT,
            ],
        ];
    }
}
