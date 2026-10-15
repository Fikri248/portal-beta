<?php

namespace App\Enums;

enum RecommendationExecution: string
{
    case Dilaksanakan = 'dilaksanakan';
    case TidakDilaksanakan = 'tidak_dilaksanakan';

    public function label(): string
    {
        return match ($this) {
            self::Dilaksanakan => 'Dilaksanakan',
            self::TidakDilaksanakan => 'Tidak Dilaksanakan',
        };
    }
}
