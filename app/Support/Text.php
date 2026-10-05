<?php

namespace App\Support;

/**
 * Input cleaning that is safe for hostile input. A client can send `field[]=x` where text is expected;
 * these helpers only touch real strings and pass everything else through unchanged, so the normal
 * validation rules (`string`, `email`…) reject it with a 422 instead of crashing with a 500.
 */
final class Text
{
    public static function trim(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    public static function lower(mixed $value): mixed
    {
        return is_string($value) ? mb_strtolower(trim($value)) : $value;
    }

    /** Removes all whitespace (tracking numbers, pincodes). */
    public static function squash(mixed $value): mixed
    {
        return is_string($value) ? preg_replace('/\s+/', '', $value) : $value;
    }

    /** Always a string: for building rate-limit keys from untrusted input. */
    public static function key(mixed $value): string
    {
        return is_string($value) ? mb_strtolower(trim($value)) : '';
    }
}
