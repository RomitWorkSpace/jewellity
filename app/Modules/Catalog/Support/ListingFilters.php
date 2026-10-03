<?php

namespace App\Modules\Catalog\Support;

use App\Support\Money;
use Illuminate\Http\Request;

/**
 * Sanitised storefront listing filters. Built from the query string; anything invalid is
 * silently dropped (public pages should never 422 on a hand-edited URL).
 */
class ListingFilters
{
    public const SORTS = [
        'newest' => 'Newest',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'name_asc' => 'Name: A–Z',
    ];

    private const MAX_ATTRS = 10;

    private const MAX_VALUES = 20;

    /**
     * @param  array<string, list<string>>  $attrs  attribute slug => value slugs
     */
    public function __construct(
        public readonly string $sort = 'newest',
        public readonly bool $sortGiven = false,
        public readonly ?int $minPrice = null,
        public readonly ?int $maxPrice = null,
        public readonly bool $inStock = false,
        public readonly array $attrs = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->query('sort');
        $sortValid = is_string($sort) && array_key_exists($sort, self::SORTS);

        $min = self::toMinor($request->query('min_price'));
        $max = self::toMinor($request->query('max_price'));
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $attrs = [];
        $raw = $request->query('attr');
        if (is_array($raw)) {
            foreach (array_slice($raw, 0, self::MAX_ATTRS, true) as $slug => $values) {
                if (! is_string($slug) || ! preg_match('/^[a-z0-9_-]{1,100}$/', $slug)) {
                    continue;
                }
                $clean = collect((array) $values)
                    ->filter(fn ($v) => is_string($v) && preg_match('/^[a-z0-9_-]{1,100}$/', $v))
                    ->unique()->take(self::MAX_VALUES)->values()->all();
                if ($clean) {
                    $attrs[$slug] = $clean;
                }
            }
        }

        return new self(
            sort: $sortValid ? $sort : 'newest',
            sortGiven: $sortValid,
            minPrice: $min,
            maxPrice: $max,
            inStock: $request->boolean('in_stock'),
            attrs: $attrs,
        );
    }

    /** "499.50" -> 49950; anything else -> null. String maths, no float rounding. */
    private static function toMinor(mixed $value): ?int
    {
        if (! is_string($value) || ! preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', trim($value), $m)) {
            return null;
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    private static function toMajor(int $minor): string
    {
        return $minor % 100 === 0 ? (string) intdiv($minor, 100) : sprintf('%d.%02d', intdiv($minor, 100), $minor % 100);
    }

    public function isFiltered(): bool
    {
        return $this->minPrice !== null || $this->maxPrice !== null || $this->inStock || $this->attrs !== [];
    }

    /** Anything beyond plain page navigation: used to keep near-duplicate URLs out of search indexes. */
    public function isRefined(): bool
    {
        return $this->isFiltered() || $this->sortGiven && $this->sort !== 'newest';
    }

    public function activeValueCount(): int
    {
        return (int) ($this->minPrice !== null || $this->maxPrice !== null) + (int) $this->inStock
            + array_sum(array_map('count', $this->attrs));
    }

    /**
     * Query-string array that reproduces these filters (for links/pagination).
     *
     * @param  list<string>  $drop  groups to omit: 'price', 'stock', 'sort', or an attribute slug
     * @return array<string, mixed>
     */
    public function toQuery(array $drop = [], array $dropValue = []): array
    {
        $q = [];

        if ($this->sortGiven && ! in_array('sort', $drop, true)) {
            $q['sort'] = $this->sort;
        }
        if (! in_array('price', $drop, true)) {
            if ($this->minPrice !== null) {
                $q['min_price'] = self::toMajor($this->minPrice);
            }
            if ($this->maxPrice !== null) {
                $q['max_price'] = self::toMajor($this->maxPrice);
            }
        }
        if ($this->inStock && ! in_array('stock', $drop, true)) {
            $q['in_stock'] = 1;
        }
        foreach ($this->attrs as $slug => $values) {
            if (in_array($slug, $drop, true)) {
                continue;
            }
            $values = array_values(array_filter($values, fn ($v) => $dropValue !== [$slug, $v]));
            if ($values) {
                $q['attr'][$slug] = $values;
            }
        }

        return $q;
    }

    /** Human label for the price range chip. */
    public function priceLabel(): ?string
    {
        return match (true) {
            $this->minPrice !== null && $this->maxPrice !== null => Money::format($this->minPrice).' – '.Money::format($this->maxPrice),
            $this->minPrice !== null => 'From '.Money::format($this->minPrice),
            $this->maxPrice !== null => 'Up to '.Money::format($this->maxPrice),
            default => null,
        };
    }

    public function minPriceInput(): string
    {
        return $this->minPrice === null ? '' : self::toMajor($this->minPrice);
    }

    public function maxPriceInput(): string
    {
        return $this->maxPrice === null ? '' : self::toMajor($this->maxPrice);
    }
}
