<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class ApplicationDateTime
{
    public static function format(?DateTimeInterface $value, string $format = 'd/m/Y H:i', bool $includeTimezone = true): string
    {
        if ($value === null) {
            return '—';
        }

        $date = Carbon::instance($value)->setTimezone(config('app.timezone'));

        return $date->format($format).($includeTimezone ? ' '.$date->format('T') : '');
    }

    public static function iso8601(?DateTimeInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::instance($value)->setTimezone(config('app.timezone'))->toIso8601String();
    }
}
