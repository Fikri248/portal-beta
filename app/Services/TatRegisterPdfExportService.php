<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class TatRegisterPdfExportService
{
    /** @param array<string, mixed> $filters @param array<string, mixed> $metadata */
    public function download(Collection $records, array $filters, array $metadata): Response
    {
        return Pdf::loadView('reports.tat.pdf', [
            'records' => $records,
            'filters' => $filters,
            'metadata' => $metadata,
            'showBnnk' => $metadata['show_bnnk'],
        ])->setPaper('a4', 'landscape')->download($this->filename($filters));
    }

    /** @param array<string, mixed> $filters */
    private function filename(array $filters): string
    {
        $period = collect([$filters['start_date'] ?? null, $filters['end_date'] ?? null])
            ->filter()
            ->implode('_sampai_');

        return 'Laporan_Register_TAT_'.($period ?: now()->format('Y-m-d')).'.pdf';
    }
}
