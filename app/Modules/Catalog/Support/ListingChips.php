<?php

namespace App\Modules\Catalog\Support;

use Illuminate\Support\Collection;

/** Removable "active filter" chips, each linking to the same listing without that one filter. */
final class ListingChips
{
    /**
     * @param  Collection<string, Collection<int, object>>  $facets
     * @param  array<string, mixed>  $persist  query params kept on every link (e.g. the search term)
     * @return list<array{label: string, url: string}>
     */
    public static function build(ListingFilters $filters, Collection $facets, string $baseUrl, array $persist = []): array
    {
        $link = fn (array $q) => $baseUrl.($persist + $q ? '?'.http_build_query($persist + $q) : '');
        $chips = [];

        if ($label = $filters->priceLabel()) {
            $chips[] = ['label' => $label, 'url' => $link($filters->toQuery(['price']))];
        }
        if ($filters->inStock) {
            $chips[] = ['label' => 'In stock', 'url' => $link($filters->toQuery(['stock']))];
        }
        foreach ($filters->attrs as $attrSlug => $values) {
            $group = $facets->get($attrSlug);
            foreach ($values as $valueSlug) {
                $value = $group?->firstWhere('slug', $valueSlug);
                $chips[] = [
                    'label' => ($group?->first()->attribute ?? $attrSlug).': '.($value->value ?? $valueSlug),
                    'url' => $link($filters->toQuery([], [$attrSlug, $valueSlug])),
                ];
            }
        }

        return $chips;
    }
}
