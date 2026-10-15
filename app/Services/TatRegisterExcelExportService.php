<?php

namespace App\Services;

use App\Models\TatRegister;
use Illuminate\Support\Collection;

class TatRegisterExcelExportService
{
    public const FILENAME_PREFIX = 'Laporan_Register_TAT_';

    public function __construct(private readonly XlsxWriter $writer) {}

    public function generate(Collection $records): string
    {
        return $this->writer->generate('Laporan Register TAT', $this->headers(), $records->values()->map(
            fn (TatRegister $record, int $index): array => $this->row($record, $index + 1)
        ));
    }

    /** @param array<string, mixed> $filters */
    public function filename(array $filters = []): string
    {
        $period = collect([$filters['start_date'] ?? null, $filters['end_date'] ?? null])
            ->filter()
            ->implode('_sampai_');

        return self::FILENAME_PREFIX.($period ?: now()->format('Y-m-d')).'.xlsx';
    }

    /** @return array<int, string> */
    private function headers(): array
    {
        return [
            'NO', 'NO/BLN', 'BNNK', 'NO REGISTER', 'NAMA', 'NIK', 'TANGGAL LAHIR',
            'TANGGAL PELAKSANAAN', 'NO LKN/LP/LI', 'PASAL YANG DISANGKAKAN', 'JENIS NARKOTIKA',
            'JUMLAH / BERAT', 'SATUAN', 'NO HANDPHONE', 'HASIL ASESMEN HUKUM', 'HASIL ASESMEN MEDIS',
            'REKOMENDASI TAT', 'PELAKSANAAN REKOMENDASI', 'HASIL REKOM TAT', 'KETERANGAN',
        ];
    }

    /** @return array<int, string> */
    private function row(TatRegister $record, int $number): array
    {
        return [
            (string) $number,
            (string) ($record->no_bln ?? '—'),
            $record->bnnk?->name ?? '—',
            $record->no_register ?? '—',
            $record->nama ?? '—',
            $record->nik ?? '—',
            $record->tanggal_lahir?->format('d/m/Y') ?? '—',
            $record->tanggal_pelaksanaan?->format('d/m/Y') ?? '—',
            $record->no_lkn_lp_li ?? '—',
            $record->pasal_yang_disangkakan ?? '—',
            $this->narcoticDetails($record),
            $this->narcoticQuantities($record),
            $this->narcoticUnits($record),
            $record->no_handphone ?? '—',
            $record->hasil_asesmen_hukum ?? '—',
            $record->hasil_asesmen_medis ?? '—',
            $record->rekomendasi_tat?->label() ?? '—',
            $record->pelaksanaan_rekomendasi?->label() ?? '—',
            $record->hasil_rekom_tat ?? '—',
            $record->keterangan ?? '—',
        ];
    }

    private function narcoticDetails(TatRegister $record): string
    {
        return $record->narcotics->map(function ($narcotic): string {
            $name = $narcotic->narcoticType?->name ?? '—';
            $quantity = $narcotic->jumlah === null ? null : number_format((float) $narcotic->jumlah, 3, '.', '');
            $unit = $narcotic->unit?->name;

            return $quantity === null ? $name : $name.' ('.$quantity.($unit ? ' '.$unit : '').')';
        })->implode('; ') ?: '—';
    }

    private function narcoticQuantities(TatRegister $record): string
    {
        return $record->narcotics->map(fn ($narcotic): string => $narcotic->jumlah === null ? '—' : number_format((float) $narcotic->jumlah, 3, '.', ''))->implode('; ') ?: '—';
    }

    private function narcoticUnits(TatRegister $record): string
    {
        return $record->narcotics->map(fn ($narcotic): string => $narcotic->unit?->name ?? '—')->implode('; ') ?: '—';
    }
}
