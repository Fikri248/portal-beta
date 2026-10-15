<?php

namespace App\Support;

use Illuminate\Http\Request;

final class Pagination
{
    /**
     * Pilihan jumlah data per halaman yang tersedia di seluruh daftar portal.
     *
     * @return list<int>
     */
    public static function options(): array
    {
        return [25, 50, 100, 250, 500];
    }

    public static function default(): int
    {
        return 25;
    }

    /**
     * Ambil per_page dari query string, fallback ke default bila tidak valid.
     */
    public static function resolve(Request $request, ?int $default = null): int
    {
        $default ??= self::default();
        $perPage = (int) $request->query('per_page', $default);

        return in_array($perPage, self::options(), true) ? $perPage : $default;
    }
}
