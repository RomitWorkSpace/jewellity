<?php

namespace App\Modules\Catalog\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Queries\ProductListingQuery;
use App\Modules\Catalog\Support\CategoryTree;
use App\Modules\Catalog\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Product listings: a category (with its sub-categories) or the whole shop. */
class ListingController extends Controller
{
    public function category(Request $request, string $slug, ProductListingQuery $listing): View
    {
        $tree = new CategoryTree;
        $category = $tree->findBySlug($slug);

        abort_if($category === null, 404);

        return $this->render($request, $listing, $tree, $category);
    }

    public function shop(Request $request, ProductListingQuery $listing): View
    {
        return $this->render($request, $listing, new CategoryTree, null);
    }

    private function render(Request $request, ProductListingQuery $listing, CategoryTree $tree, ?Category $category): View
    {
        $filters = ListingFilters::fromRequest($request);
        $categoryIds = $category ? $tree->idsWithDescendants($category) : null;
        $baseUrl = $category ? url('/category/'.$category->slug) : url('/shop');

        $products = $listing->paginate($categoryIds, $filters, (int) config('storefront.listing.per_page'));

        // Walking past the last page is a dead end, not an empty listing.
        abort_if($products->currentPage() > 1 && $products->isEmpty(), 404);

        $facets = $listing->attributeFacets($categoryIds);
        $priceBounds = $listing->priceBounds($categoryIds);

        $heading = $category?->name
            ?? ($filters->sortGiven && $filters->sort === 'newest' && ! $filters->isFiltered() ? 'New Arrivals' : 'All Jewellery');

        $breadcrumbs = [['name' => 'Home', 'url' => url('/')]];
        if ($category) {
            foreach ($tree->ancestors($category) as $ancestor) {
                $breadcrumbs[] = ['name' => $ancestor->name, 'url' => url('/category/'.$ancestor->slug)];
            }
            $breadcrumbs[] = ['name' => $category->name, 'url' => $baseUrl];
        } else {
            $breadcrumbs[] = ['name' => $heading, 'url' => $baseUrl];
        }

        $page = $products->currentPage();
        $seo = [
            'title' => ($category?->meta_title ?: $heading.' – '.config('app.name')).($page > 1 ? " (Page $page)" : ''),
            'description' => $category?->meta_description
                ?: ($category?->description ? str($category->description)->limit(155)->toString()
                    : "Shop {$heading} at ".config('app.name').'. Statement pieces and everyday favourites, designed to sparkle.'),
            // Filtered/sorted variants are duplicates of the base listing: keep them out of the index.
            'canonical' => $baseUrl.($page > 1 && ! $filters->isRefined() ? '?page='.$page : ''),
            'noindex' => $filters->isRefined(),
            'prev' => $products->previousPageUrl(),
            'next' => $products->nextPageUrl(),
            'jsonLd' => $this->jsonLd($breadcrumbs, $heading, $baseUrl, $products->getCollection()),
        ];

        return view('storefront.listing', [
            'category' => $category,
            'heading' => $heading,
            'description' => $category?->description,
            'children' => $category ? $tree->children($category) : $tree->roots(),
            'breadcrumbs' => $breadcrumbs,
            'products' => $products,
            'facets' => $facets,
            'priceBounds' => $priceBounds,
            'filters' => $filters,
            'chips' => $this->chips($filters, $facets, $baseUrl),
            'baseUrl' => $baseUrl,
            'seo' => $seo,
        ]);
    }

    /** Removable "active filter" chips, each linking to the same listing without that one filter. */
    private function chips(ListingFilters $filters, $facets, string $baseUrl): array
    {
        $link = fn (array $q) => $baseUrl.($q ? '?'.http_build_query($q) : '');
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

    private function jsonLd(array $crumbs, string $heading, string $url, $products): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => collect($crumbs)->values()->map(fn ($c, $i) => [
                        '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url'],
                    ])->all(),
                ],
                [
                    '@type' => 'CollectionPage',
                    'name' => $heading,
                    'url' => $url,
                    'mainEntity' => [
                        '@type' => 'ItemList',
                        'itemListElement' => $products->values()->map(fn ($p, $i) => [
                            '@type' => 'ListItem', 'position' => $i + 1, 'url' => url('/product/'.$p->slug), 'name' => $p->name,
                        ])->all(),
                    ],
                ],
            ],
        ];
    }
}
