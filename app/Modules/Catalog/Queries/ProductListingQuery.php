<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Support\ListingFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Storefront product listing + its filter facets. `$categoryIds = null` means the whole shop. */
class ProductListingQuery
{
    public function paginate(?array $categoryIds, ListingFilters $filters, int $perPage): LengthAwarePaginator
    {
        $query = Product::published()->purchasable()
            ->with(['variants', 'images'])
            ->when($categoryIds !== null, fn (Builder $q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)));

        // Price, stock and attributes must all hold for the SAME variant, so
        // "Gold + Medium" means a variant that is both, not one of each.
        if ($filters->isFiltered()) {
            $query->whereHas('variants', function (Builder $v) use ($filters) {
                $v->where('is_active', true);

                if ($filters->minPrice !== null) {
                    $v->where('price', '>=', $filters->minPrice);
                }
                if ($filters->maxPrice !== null) {
                    $v->where('price', '<=', $filters->maxPrice);
                }
                if ($filters->inStock) {
                    $v->where(fn ($s) => $s->where('track_inventory', false)->orWhere('allow_backorder', true)->orWhere('stock_quantity', '>', 0));
                }
                foreach ($filters->attrs as $attributeSlug => $valueSlugs) {
                    $v->whereHas('attributeValues', fn ($av) => $av
                        ->whereIn('attribute_values.slug', $valueSlugs)
                        ->whereHas('attribute', fn ($a) => $a->where('slug', $attributeSlug)));
                }
            });
        }

        match ($filters->sort) {
            'price_asc', 'price_desc' => $query
                ->withMin(['variants as min_price' => fn ($v) => $v->where('is_active', true)], 'price')
                ->orderBy('min_price', $filters->sort === 'price_asc' ? 'asc' : 'desc'),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest('published_at'),
        };

        // `id` as the final tiebreaker keeps pagination stable.
        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Attribute values used by purchasable products here, grouped by attribute.
     *
     * @return Collection<string, Collection<int, object>> keyed by attribute slug
     */
    public function attributeFacets(?array $categoryIds): Collection
    {
        return $this->scopedVariants($categoryIds)
            ->join('variant_attribute_value as vav', 'vav.product_variant_id', '=', 'v.id')
            ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
            ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
            ->select('a.name as attribute', 'a.slug as attribute_slug', 'av.value', 'av.slug', 'av.sort_order')
            ->distinct()
            ->orderBy('a.name')->orderBy('av.sort_order')->orderBy('av.value')
            ->get()
            ->groupBy('attribute_slug');
    }

    /** @return array{min: ?int, max: ?int} price range of purchasable variants, in minor units */
    public function priceBounds(?array $categoryIds): array
    {
        $row = $this->scopedVariants($categoryIds)->selectRaw('MIN(v.price) as lo, MAX(v.price) as hi')->first();

        return ['min' => $row?->lo !== null ? (int) $row->lo : null, 'max' => $row?->hi !== null ? (int) $row->hi : null];
    }

    /** Active variants of published products (optionally within categories). */
    private function scopedVariants(?array $categoryIds): QueryBuilder
    {
        return DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.is_active', true)->whereNull('v.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('p.status', ProductStatus::Active->value)
            ->where(fn ($q) => $q->whereNull('p.published_at')->orWhere('p.published_at', '<=', now()))
            ->when($categoryIds !== null, fn ($q) => $q->whereExists(fn ($e) => $e
                ->select(DB::raw(1))->from('category_product as cp')
                ->whereColumn('cp.product_id', 'p.id')->whereIn('cp.category_id', $categoryIds)));
    }
}
