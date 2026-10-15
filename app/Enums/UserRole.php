<?php

namespace App\Enums;

enum UserRole: string
{
    case Bnnp = 'bnnp';
    case Bnnk = 'bnnk';

    public function label(): string
    {
        return match ($this) {
            self::Bnnp => 'BNNP',
            self::Bnnk => 'BNNK',
        };
    }
}
