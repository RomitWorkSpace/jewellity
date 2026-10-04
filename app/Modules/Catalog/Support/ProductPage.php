<?php

namespace App\Modules\Catalog\Support;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Everything the product page needs, prepared once: the variant/option payload the browser
 * uses for selection, the image list, breadcrumbs and schema.org data.
 * Expects `variants.attributeValues.attribute`, `images` and `categories` to be loaded.
 */
class ProductPage
{
    public function __construct(private Product $product, private CategoryTree $tree) {}

    /** @return Collection<int, ProductVariant> */
    private function variants()
    {
        return $this->product->variants->where('is_active', true)->values();
    }

    /** The variant to show first: a valid ?variant=, else the default/cheapest. */
    public function initialVariant(?int $requested): ProductVariant
    {
        return $this->variants()->firstWhere('id', $requested) ?? $this->product->displayVariant();
    }

    /** Breadcrumb trail ending at the product, using the deepest reachable category. */
    public function breadcrumbs(): array
    {
        $deepest = $this->product->categories
            ->map(fn ($c) => $this->tree->findBySlug($c->slug))
            ->filter()
            ->sortByDesc(fn ($c) => count($this->tree->ancestors($c)))
            ->first();

        $crumbs = [['name' => 'Home', 'url' => url('/')]];

        if ($deepest) {
            foreach ($this->tree->ancestors($deepest) as $ancestor) {
                $crumbs[] = ['name' => $ancestor->name, 'url' => url('/category/'.$ancestor->slug)];
            }
            $crumbs[] = ['name' => $deepest->name, 'url' => url('/category/'.$deepest->slug)];
        }

        $crumbs[] = ['name' => $this->product->name, 'url' => url('/product/'.$this->product->slug)];

        return $crumbs;
    }

    /** The category used for "more from…" links and related products, if any. */
    public function primaryCategoryId(): ?int
    {
        $deepest = $this->product->categories
            ->map(fn ($c) => $this->tree->findBySlug($c->slug))->filter()
            ->sortByDesc(fn ($c) => count($this->tree->ancestors($c)))->first();

        return $deepest?->id;
    }

    /** @return list<array{url: string, alt: string, variant_id: ?int}> */
    public function images(): array
    {
        return $this->product->images->map(fn ($i) => [
            'url' => $i->url(),
            'alt' => $i->alt ?: $this->product->name,
            'variant_id' => $i->product_variant_id,
        ])->all();
    }

    /**
     * Selectable options, e.g. [{slug: colour, name: Colour, values: [{slug: gold, label: Gold}]}].
     * Variants with no attributes at all fall back to a single "Option" group labelled by SKU.
     *
     * @return array{groups: list<array<string, mixed>>, variantAttrs: array<int, array<string, string>>}
     */
    public function options(): array
    {
        $variants = $this->variants();
        $groups = [];
        $variantAttrs = [];

        foreach ($variants as $variant) {
            $variantAttrs[$variant->id] = [];
            foreach ($variant->attributeValues as $value) {
                $slug = $value->attribute->slug;
                $groups[$slug] ??= ['slug' => $slug, 'name' => $value->attribute->name, 'values' => [], 'order' => []];
                $groups[$slug]['values'][$value->slug] ??= ['slug' => $value->slug, 'label' => $value->value, 'sort' => $value->sort_order];
                $variantAttrs[$variant->id][$slug] = $value->slug;
            }
        }

        if (! $groups && $variants->count() > 1) {
            $groups['option'] = ['slug' => 'option', 'name' => 'Option', 'values' => []];
            foreach ($variants as $variant) {
                $groups['option']['values']['v'.$variant->id] = ['slug' => 'v'.$variant->id, 'label' => $variant->sku, 'sort' => $variant->sort_order];
                $variantAttrs[$variant->id] = ['option' => 'v'.$variant->id];
            }
        }

        $groups = collect($groups)->sortBy('name')->map(function ($g) {
            $g['values'] = collect($g['values'])->sortBy([['sort', 'asc'], ['label', 'asc']])
                ->map(fn ($v) => ['slug' => $v['slug'], 'label' => $v['label']])->values()->all();
            unset($g['order']);

            return $g;
        })->values()->all();

        return ['groups' => $groups, 'variantAttrs' => $variantAttrs];
    }

    /** Variant data for the browser (prices pre-formatted so JS never formats money). */
    public function variantPayload(): array
    {
        $images = $this->product->images->values();
        $attrs = $this->options()['variantAttrs'];

        return $this->variants()->map(function (ProductVariant $v) use ($images, $attrs) {
            $limited = $v->track_inventory && ! $v->allow_backorder;
            $discount = $v->compare_at_price && $v->compare_at_price > $v->price
                ? (int) round((1 - $v->price / $v->compare_at_price) * 100) : null;

            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'price' => Money::format($v->price),
                'compare' => $discount ? Money::format($v->compare_at_price) : null,
                'discount' => $discount,
                'inStock' => $v->isInStock(),
                // Units that can be bought at once: null = no stock limit.
                'maxQty' => $limited ? max(0, $v->stock_quantity) : null,
                'lowStock' => $limited && $v->stock_quantity > 0 && $v->stock_quantity <= $v->low_stock_threshold,
                'stockLeft' => $limited ? $v->stock_quantity : null,
                'weight' => $v->weight_grams,
                'attrs' => (object) ($attrs[$v->id] ?? []),
                'details' => $v->attributeValues->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])->all(),
                'imageIndex' => ($i = $images->search(fn ($img) => $img->product_variant_id === $v->id)) === false ? null : $i,
            ];
        })->all();
    }

    /** schema.org Product (with Offer / AggregateOffer) + BreadcrumbList. */
    public function jsonLd(): array
    {
        $p = $this->product;
        $variants = $this->variants();
        $currency = config('catalog.currency');
        $decimal = fn (int $minor) => number_format($minor / 100, 2, '.', '');
        $availability = fn (ProductVariant $v) => $v->isInStock()
            ? ($v->track_inventory && $v->allow_backorder && $v->stock_quantity <= 0 ? 'https://schema.org/BackOrder' : 'https://schema.org/InStock')
            : 'https://schema.org/OutOfStock';

        $display = $p->displayVariant();
        $url = url('/product/'.$p->slug);

        $offers = $variants->count() > 1
            ? [
                '@type' => 'AggregateOffer',
                'priceCurrency' => $currency,
                'lowPrice' => $decimal($variants->min('price')),
                'highPrice' => $decimal($variants->max('price')),
                'offerCount' => $variants->count(),
                'availability' => $p->isSoldOut() ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
                'url' => $url,
            ]
            : [
                '@type' => 'Offer',
                'priceCurrency' => $currency,
                'price' => $decimal($display->price),
                'availability' => $availability($display),
                'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $url,
            ];

        $product = array_filter([
            '@type' => 'Product',
            'name' => $p->name,
            'description' => strip_tags($p->short_description ?: (string) str($p->description)->limit(300)),
            'sku' => $display->sku,
            'image' => array_column($this->images(), 'url') ?: null,
            'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
            'offers' => $offers,
            'url' => $url,
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $product,
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => collect($this->breadcrumbs())->values()->map(fn ($c, $i) => [
                        '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url'],
                    ])->all(),
                ],
            ],
        ];
    }
}
