<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Enums\TatRegisterStatus;
use App\Models\Bnnk;
use App\Models\TatRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardChartService
{
    /**
     * Warna kategori chart mengikuti urutan slot yang konsisten,
     * agar identitas warna tidak berubah antar filter.
     */
    public const RECOMMENDATION_COLORS = [
        'rawat_jalan' => '#0984E2',
        'rawat_inap' => '#eb6834',
        'rehab_lapas_rutan' => '#1baf7a',
        'tidak_rehab' => '#eda100',
    ];

    public const GENDER_COLORS = [
        'L' => '#0984E2',
        'P' => '#e87ba4',
    ];

    public const EXECUTION_COLORS = [
        'dilaksanakan' => '#1baf7a',
        'tidak_dilaksanakan' => '#eda100',
    ];

    public const STATUS_COLORS = [
        'final' => '#0984E2',
        'draft' => '#eda100',
        'arsip' => '#898781',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $scope = $this->scoped($user);
        $months = $this->lastMonths(6);

        $active = (clone $scope)->whereNull('deleted_at');
        $total = (clone $active)->count();
        $final = (clone $active)->where('status', TatRegisterStatus::Final->value)->count();
        $draft = (clone $active)->where('status', TatRegisterStatus::Draft->value)->count();
        $archived = (clone $scope)->whereNotNull('deleted_at')->count();
        $thisMonth = (clone $active)
            ->where('no_bln_month', now()->month)
            ->where('no_bln_year', now()->year)
            ->whereNotNull('no_bln')
            ->count();

        $data = [
            'kpi' => [
                ['label' => 'Total Register', 'value' => $total, 'hint' => 'Final + Draft aktif'],
                ['label' => 'Final', 'value' => $final, 'hint' => 'Siap dilaporkan'],
                ['label' => 'Draft', 'value' => $draft, 'hint' => 'Perlu dilengkapi'],
                ['label' => 'Arsip', 'value' => $archived, 'hint' => 'Sudah diarsipkan'],
                ['label' => 'Bulan berjalan', 'value' => $thisMonth, 'hint' => now()->translatedFormat('F Y')],
            ],
            'monthly' => $this->monthlyTrend(clone $active, $months),
            'recommendation' => $this->byEnum(clone $active, 'rekomendasi_tat', TatRecommendation::cases(), self::RECOMMENDATION_COLORS),
            'execution' => $this->byEnum(clone $active, 'pelaksanaan_rekomendasi', RecommendationExecution::cases(), self::EXECUTION_COLORS),
            'gender' => $this->byEnum(clone $active, 'jenis_kelamin', Gender::cases(), self::GENDER_COLORS),
            'status' => $this->statusBreakdown(clone $scope),
        ];

        $data['topNarcotics'] = $this->topNarcotics($user);

        if ($user->isBnnp()) {
            $data['perBnnk'] = $this->perBnnk(clone $scope);
        }

        return $data;
    }

    private function scoped(User $user): Builder
    {
        $query = TatRegister::query()->withTrashed();

        if ($user->isBnnk()) {
            $query->where('bnnk_id', $user->bnnk_id);
        }

        return $query;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function lastMonths(int $count): array
    {
        $months = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $months[] = [
                'key' => $date->format('Y-m'),
                'label' => $date->translatedFormat('M Y'),
                'month' => $date->month,
                'year' => $date->year,
            ];
        }

        return $months;
    }

    /**
     * @param  array<int, array{key: string, label: string, month: int, year: int}>  $months
     * @return array{labels: array<int, string>, final: array<int, int>, draft: array<int, int>}
     */
    private function monthlyTrend(Builder $scope, array $months): array
    {
        $driver = $scope->getModel()->getConnection()->getDriverName();
        $periodExpr = $driver === 'mysql'
            ? "DATE_FORMAT(tanggal_pelaksanaan, '%Y-%m')"
            : 'strftime("%Y-%m", tanggal_pelaksanaan)';

        $grouped = (clone $scope)
            ->selectRaw("{$periodExpr} as period, status, COUNT(*) as total")
            ->whereNotNull('tanggal_pelaksanaan')
            ->whereDate('tanggal_pelaksanaan', '>=', now()->startOfMonth()->subMonths(count($months) - 1)->toDateString())
            ->groupBy('period', 'status')
            ->get();

        $labels = [];
        $final = [];
        $draft = [];

        foreach ($months as $month) {
            $labels[] = $month['label'];
            $final[] = (int) $grouped
                ->where('period', $month['key'])
                ->where('status', TatRegisterStatus::Final->value)
                ->sum('total');
            $draft[] = (int) $grouped
                ->where('period', $month['key'])
                ->where('status', TatRegisterStatus::Draft->value)
                ->sum('total');
        }

        return ['labels' => $labels, 'final' => $final, 'draft' => $draft];
    }

    /**
     * @param  array<int, object{value: string}>  $cases
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>}
     */
    private function byEnum(Builder $scope, string $column, array $cases, array $colors): array
    {
        $counts = (clone $scope)
            ->selectRaw("{$column} as bucket, COUNT(*) as total")
            ->whereNotNull($column)
            ->groupBy($column)
            ->pluck('total', 'bucket');

        $labels = [];
        $values = [];
        $seriesColors = [];

        foreach ($cases as $case) {
            $labels[] = $case->label();
            $values[] = (int) ($counts[$case->value] ?? 0);
            $seriesColors[] = $colors[$case->value] ?? '#898781';
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $seriesColors];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>}
     */
    private function statusBreakdown(Builder $scope): array
    {
        $active = (clone $scope)->whereNull('deleted_at');

        return [
            'labels' => ['Final', 'Draft', 'Arsip'],
            'values' => [
                (clone $active)->where('status', TatRegisterStatus::Final->value)->count(),
                (clone $active)->where('status', TatRegisterStatus::Draft->value)->count(),
                (clone $scope)->whereNotNull('deleted_at')->count(),
            ],
            'colors' => [self::STATUS_COLORS['final'], self::STATUS_COLORS['draft'], self::STATUS_COLORS['arsip']],
        ];
    }

    /**
     * @return array{labels: array<int, string>, final: array<int, int>, draft: array<int, int>}
     */
    private function perBnnk(Builder $scope): array
    {
        $grouped = (clone $scope)
            ->selectRaw('bnnk_id, status, COUNT(*) as total')
            ->whereNull('deleted_at')
            ->groupBy('bnnk_id', 'status')
            ->get()
            ->groupBy('bnnk_id');

        $bnnks = Bnnk::query()
            ->whereIn('id', $grouped->keys()->all())
            ->orderBy('name')
            ->get();

        if ($bnnks->isEmpty()) {
            $bnnks = Bnnk::query()->orderBy('name')->limit(10)->get();
        }

        $labels = [];
        $final = [];
        $draft = [];

        foreach ($bnnks as $bnnk) {
            $labels[] = $this->shortBnnkName($bnnk->name);
            $rows = $grouped->get($bnnk->id, collect());
            $final[] = (int) $rows->where('status', TatRegisterStatus::Final->value)->sum('total');
            $draft[] = (int) $rows->where('status', TatRegisterStatus::Draft->value)->sum('total');
        }

        return ['labels' => $labels, 'final' => $final, 'draft' => $draft];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function topNarcotics(User $user): array
    {
        $query = TatRegister::query()->whereNull('deleted_at');

        if ($user->isBnnk()) {
            $query->where('bnnk_id', $user->bnnk_id);
        }

        $rows = $query
            ->join('tat_narcotics', 'tat_narcotics.tat_register_id', '=', 'tat_registers.id')
            ->join('narcotic_types', 'narcotic_types.id', '=', 'tat_narcotics.narcotic_type_id')
            ->selectRaw('narcotic_types.name as name, COUNT(*) as total')
            ->groupBy('narcotic_types.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    private function shortBnnkName(string $name): string
    {
        $short = preg_replace('/^BNNK?\s+(Kota|Kabupaten)\s+/i', '', $name);

        return mb_strlen($short) > 14 ? mb_substr($short, 0, 13).'…' : $short;
    }
}
