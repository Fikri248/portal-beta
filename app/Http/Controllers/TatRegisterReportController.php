<?php

namespace App\Http\Controllers;

use App\Enums\AuditLogAction;
use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Http\Requests\TatRegisterReportRequest;
use App\Models\Bnnk;
use App\Models\NarcoticType;
use App\Services\TatRegisterExcelExportService;
use App\Services\TatRegisterPdfExportService;
use App\Services\TatRegisterReportService;
use App\Services\AuditLogService;
use App\Support\ApplicationDateTime;
use App\Support\Pagination;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TatRegisterReportController extends Controller
{
    public function __construct(
        private readonly TatRegisterReportService $reportService,
        private readonly TatRegisterExcelExportService $excelExportService,
        private readonly TatRegisterPdfExportService $pdfExportService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(TatRegisterReportRequest $request): View
    {
        $filters = $request->filters();
        $perPage = Pagination::resolve($request);
        $query = $this->reportService->query($request->user(), $filters);
        $records = (clone $query)
            ->with(['bnnk', 'narcotics.narcoticType', 'narcotics.unit'])
            ->orderByDesc('tanggal_pelaksanaan')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('reports.tat.index', [
            'records' => $records,
            'perPage' => $perPage,
            'filters' => $filters,
            'summary' => $this->reportService->summary($query),
            ...$this->filterOptions($request),
        ]);
    }

    public function exportExcel(TatRegisterReportRequest $request): Response
    {
        $filters = $request->filters();
        $records = $this->exportRecords($request, $filters);
        $filename = $this->excelExportService->filename($filters);
        $content = $this->excelExportService->generate($records);

        $this->auditLogService->record(
            AuditLogAction::ReportExportedExcel,
            metadata: ['filter_summary' => $filters],
            actor: $request->user(),
        );

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function exportPdf(TatRegisterReportRequest $request): Response
    {
        $filters = $request->filters();

        $response = $this->pdfExportService->download(
            $this->exportRecords($request, $filters),
            $filters,
            $this->metadata($request, $filters)
        );

        $this->auditLogService->record(
            AuditLogAction::ReportExportedPdf,
            metadata: ['filter_summary' => $filters],
            actor: $request->user(),
        );

        return $response;
    }

    /** @param array<string, mixed> $filters */
    private function exportRecords(TatRegisterReportRequest $request, array $filters)
    {
        return $this->reportService->query($request->user(), $filters)
            ->with(['bnnk', 'narcotics.narcoticType', 'narcotics.unit'])
            ->orderByDesc('tanggal_pelaksanaan')
            ->orderByDesc('id')
            ->get();
    }

    /** @return array<string, mixed> */
    private function filterOptions(TatRegisterReportRequest $request): array
    {
        return [
            'bnnks' => $request->user()->isBnnp() ? Bnnk::query()->orderBy('name')->get() : collect(),
            'narcoticTypes' => NarcoticType::query()->where('is_active', true)->orderBy('name')->get(),
            'recommendationOptions' => collect(TatRecommendation::cases())->mapWithKeys(fn (TatRecommendation $option): array => [$option->value => $option->label()])->all(),
            'executionOptions' => collect(RecommendationExecution::cases())->mapWithKeys(fn (RecommendationExecution $option): array => [$option->value => $option->label()])->all(),
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function metadata(TatRegisterReportRequest $request, array $filters): array
    {
        $user = $request->user()->loadMissing('bnnk');
        $bnnkName = $user->isBnnk()
            ? ($user->bnnk?->name ?? 'BNNK pengguna')
            : (filled($filters['bnnk_id'] ?? null) ? Bnnk::find($filters['bnnk_id'])?->name : 'Semua BNNK');

        return [
            'period' => $this->periodLabel($filters),
            'bnnk' => $bnnkName ?? 'Semua BNNK',
            'recommendation' => $this->enumLabel(TatRecommendation::class, $filters['recommendation'] ?? null),
            'execution' => $this->enumLabel(RecommendationExecution::class, $filters['execution'] ?? null),
            'narcotic_type' => filled($filters['narcotic_type_id'] ?? null) ? NarcoticType::find($filters['narcotic_type_id'])?->name : 'Semua jenis narkotika',
            'search' => $filters['search'] ?? '—',
            'generated_at' => ApplicationDateTime::format(now()),
            'show_bnnk' => $user->isBnnp(),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function periodLabel(array $filters): string
    {
        if (filled($filters['start_date'] ?? null) && filled($filters['end_date'] ?? null)) {
            return $filters['start_date'].' s.d. '.$filters['end_date'];
        }

        if (filled($filters['start_date'] ?? null)) {
            return 'Mulai '.$filters['start_date'];
        }

        if (filled($filters['end_date'] ?? null)) {
            return 'Sampai '.$filters['end_date'];
        }

        return 'Semua periode';
    }

    private function enumLabel(string $enumClass, ?string $value): string
    {
        return $value === null ? 'Semua' : $enumClass::from($value)->label();
    }
}
