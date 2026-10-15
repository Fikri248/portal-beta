<?php

namespace App\Support;

final class BnnEmail
{
    public const DOMAIN = 'bnn.go.id';

    public const SUFFIX = '@'.self::DOMAIN;

    public static function normalize(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        if (str_ends_with($value, self::SUFFIX)) {
            $localPart = substr($value, 0, -strlen(self::SUFFIX));

            return str_contains($localPart, '@') ? $value : $localPart.self::SUFFIX;
        }

        return str_contains($value, '@') ? $value : $value.self::SUFFIX;
    }

    public static function localPart(?string $value): string
    {
        $value = trim((string) $value);

        return str_ends_with(strtolower($value), self::SUFFIX)
            ? substr($value, 0, -strlen(self::SUFFIX))
            : $value;
    }

    public static function hasRequiredDomain(?string $value): bool
    {
        return str_ends_with(strtolower(trim((string) $value)), self::SUFFIX);
    }

    public static function hasValidLocalPart(?string $value): bool
    {
        return preg_match("/\\A[a-z0-9.!#$%&'*+\\-\\/=^_`{|}~]+\\z/i", (string) $value) === 1;
    }
}
