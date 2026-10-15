<?php

namespace App\Services;

use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Enums\TatRegisterStatus;
use App\Models\TatRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TatRegisterReportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(User $user, array $filters = []): Builder
    {
        $query = TatRegister::query()
            ->where('status', TatRegisterStatus::Final->value)
            ->whereNull('deleted_at');

        if ($user->isBnnk()) {
            $query->where('bnnk_id', $user->bnnk_id);
        } elseif (filled($filters['bnnk_id'] ?? null)) {
            $query->where('bnnk_id', (int) $filters['bnnk_id']);
        }

        $query
            ->when(filled($filters['start_date'] ?? null), fn (Builder $query) => $query->whereDate('tanggal_pelaksanaan', '>=', $filters['start_date']))
            ->when(filled($filters['end_date'] ?? null), fn (Builder $query) => $query->whereDate('tanggal_pelaksanaan', '<=', $filters['end_date']))
            ->when(filled($filters['recommendation'] ?? null), fn (Builder $query) => $query->where('rekomendasi_tat', $filters['recommendation']))
            ->when(filled($filters['execution'] ?? null), fn (Builder $query) => $query->where('pelaksanaan_rekomendasi', $filters['execution']))
            ->when(filled($filters['narcotic_type_id'] ?? null), fn (Builder $query) => $query->whereHas('narcotics', fn (Builder $narcotics) => $narcotics->where('narcotic_type_id', (int) $filters['narcotic_type_id'])))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nama', 'like', $search)
                        ->orWhere('no_register', 'like', $search);
                });
            });

        return $query;
    }

    /**
     * @return array{total:int,recommendations:array<string,int>,executions:array<string,int>}
     */
    public function summary(Builder $query): array
    {
        $recommendations = [];

        foreach (TatRecommendation::cases() as $recommendation) {
            $recommendations[$recommendation->value] = (clone $query)
                ->where('rekomendasi_tat', $recommendation->value)
                ->count();
        }

        $executions = [];

        foreach (RecommendationExecution::cases() as $execution) {
            $executions[$execution->value] = (clone $query)
                ->where('pelaksanaan_rekomendasi', $execution->value)
                ->count();
        }

        return [
            'total' => (clone $query)->count(),
            'recommendations' => $recommendations,
            'executions' => $executions,
        ];
    }
}
