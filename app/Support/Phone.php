<?php

namespace App\Support;

/** Indian mobile numbers: stored as the bare 10 digits. */
final class Phone
{
    /** "+91 98765-43210", "098765 43210", "9876543210" → "9876543210". Anything else → null. */
    public static function normalize(mixed $input): ?string
    {
        if (! is_string($input) && ! is_int($input)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $input);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? $digits : null;
    }
}
