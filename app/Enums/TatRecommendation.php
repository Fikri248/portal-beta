<?php

namespace App\Enums;

enum TatRecommendation: string
{
    case RawatJalan = 'rawat_jalan';
    case RawatInap = 'rawat_inap';
    case RehabLapasRutan = 'rehab_lapas_rutan';
    case TidakRehab = 'tidak_rehab';

    public function label(): string
    {
        return match ($this) {
            self::RawatJalan => 'Rawat Jalan',
            self::RawatInap => 'Rawat Inap',
            self::RehabLapasRutan => 'Rehab di Lapas/Rutan',
            self::TidakRehab => 'Tidak Rehab / Proses Hukum',
        };
    }
}
