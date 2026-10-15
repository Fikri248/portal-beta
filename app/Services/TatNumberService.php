<?php

namespace App\Services;

use App\Models\Bnnk;
use App\Models\TatRegister;
use Illuminate\Support\Carbon;

class TatNumberService
{
    /**
     * NO/BLN scope currently follows tanggal_pelaksanaan. This is an implementation assumption pending stakeholder confirmation.
     *
     * @return array{no_bln_month: int|null, no_bln_year: int|null}
     */
    public function contextFromTanggalPelaksanaan(mixed $value): array
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return ['no_bln_month' => null, 'no_bln_year' => null];
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return [
            'no_bln_month' => $date->month,
            'no_bln_year' => $date->year,
        ];
    }

    public function nextNumber(int $bnnkId, mixed $tanggalPelaksanaan, ?int $ignoreRecordId = null): ?int
    {
        $context = $this->contextFromTanggalPelaksanaan($tanggalPelaksanaan);

        if ($context['no_bln_month'] === null || $context['no_bln_year'] === null) {
            return null;
        }

        Bnnk::query()
            ->whereKey($bnnkId)
            ->lockForUpdate()
            ->firstOrFail();

        $query = TatRegister::withTrashed()
            ->where('bnnk_id', $bnnkId)
            ->where('no_bln_month', $context['no_bln_month'])
            ->where('no_bln_year', $context['no_bln_year']);

        if ($ignoreRecordId !== null) {
            $query->whereKeyNot($ignoreRecordId);
        }

        return ((int) $query->max('no_bln')) + 1;
    }

    public function isOccupied(int $bnnkId, int $month, int $year, int $number, ?int $ignoreRecordId = null): bool
    {
        $query = TatRegister::withTrashed()
            ->where('bnnk_id', $bnnkId)
            ->where('no_bln_month', $month)
            ->where('no_bln_year', $year)
            ->where('no_bln', $number);

        if ($ignoreRecordId !== null) {
            $query->whereKeyNot($ignoreRecordId);
        }

        return $query->exists();
    }
}
