<?php

namespace App\Support;

use NumberFormatter;

class Money
{
    /** Formats integer minor units (49950) as a price (₹499.50). Whole amounts drop the decimals (₹499). */
    public static function format(?int $minor, ?string $currency = null): string
    {
        if ($minor === null) {
            return '';
        }

        $formatter = new NumberFormatter(config('storefront.locale', 'en_IN'), NumberFormatter::CURRENCY);
        $whole = $minor % 100 === 0;
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $whole ? 0 : 2);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);

        return $formatter->formatCurrency($minor / 100, $currency ?? config('catalog.currency'));
    }
}
