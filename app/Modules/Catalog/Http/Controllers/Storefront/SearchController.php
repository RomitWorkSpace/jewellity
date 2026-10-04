<?php

namespace App\Modules\Catalog\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Queries\ProductListingQuery;
use App\Modules\Catalog\Support\CategoryTree;
use App\Modules\Catalog\Support\ListingChips;
use App\Modules\Catalog\Support\ListingFilters;
use App\Modules\Catalog\Support\ListingScope;
use App\Modules\Catalog\Support\SearchTerms;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const SUGGEST_PRODUCTS = 6;

    private const SUGGEST_MIN_LENGTH = 2;

    /** The results page. Filters, sorting and pagination work exactly as on a category listing. */
    public function index(Request $request, ProductListingQuery $listing): View|RedirectResponse
    {
        $search = SearchTerms::parse($request->query('q'));
        $tree = new CategoryTree;

        if ($search === null) {
            return view('storefront.search-start', [
                'categories' => $tree->roots(),
                'suggestions' => $this->popular(),
                'seo' => ['title' => 'Search – '.config('app.name')],
            ]);
        }

        $filters = ListingFilters::fromRequest($request, search: true);

        // Typing an exact SKU takes you straight to that piece.
        if (! $filters->isFiltered() && ! $request->has('page') && ($variant = $this->exactSku($search))) {
            return redirect(url('/product/'.$variant->product->slug).'?variant='.$variant->id);
        }

        $scope = new ListingScope(null, $search);
        $products = $listing->paginate($scope, $filters, (int) config('storefront.listing.per_page'));

        abort_if($products->currentPage() > 1 && $products->isEmpty(), 404);

        $facets = $listing->attributeFacets($scope);
        $baseUrl = url('/search');
        $persist = ['q' => $search->raw];

        return view('storefront.listing', [
            'category' => null,
            'heading' => 'Results for “'.$search->raw.'”',
            'description' => null,
            'children' => collect(),
            'breadcrumbs' => [['name' => 'Home', 'url' => url('/')], ['name' => 'Search', 'url' => $baseUrl]],
            'products' => $products,
            'facets' => $facets,
            'priceBounds' => $listing->priceBounds($scope),
            'filters' => $filters,
            'chips' => ListingChips::build($filters, $facets, $baseUrl, $persist),
            'sorts' => ListingFilters::sortOptions(true),
            'persist' => $persist,
            'baseUrl' => $baseUrl,
            'searchQuery' => $search->raw,
            'noResultsExtras' => $products->isEmpty() ? ['categories' => $tree->roots(), 'suggestions' => $this->popular()] : null,
            // Search result pages are thin, endlessly variable duplicates: never index them.
            'seo' => [
                'title' => 'Results for “'.$search->raw.'” – '.config('app.name'),
                'description' => null,
                'canonical' => $baseUrl,
                'noindex' => true,
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
                'jsonLd' => null,
            ],
        ]);
    }

    /** Live suggestions for the header search box. */
    public function suggest(Request $request): JsonResponse
    {
        $search = SearchTerms::parse($request->query('q'));

        if ($search === null || mb_strlen($search->raw) < self::SUGGEST_MIN_LENGTH) {
            return response()->json(['query' => $search?->raw ?? '', 'products' => [], 'categories' => [], 'total' => 0]);
        }

        $matches = fn () => $search->apply(Product::published()->purchasable());

        $products = $search->orderBy($matches())
            ->with(['variants', 'images'])
            ->limit(self::SUGGEST_PRODUCTS)->get();

        return response()->json([
            'query' => $search->raw,
            'total' => $matches()->count(),
            'products' => $products->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => url('/product/'.$p->slug),
                'price' => ($p->minPrice() !== $p->maxPrice() ? 'From ' : '').Money::format($p->minPrice()),
                'image' => $p->images->first()?->url(),
                'soldOut' => $p->isSoldOut(),
            ])->all(),
            'categories' => (new CategoryTree)->matching($search)->map(fn ($c) => [
                'name' => $c->name,
                'url' => url('/category/'.$c->slug),
            ])->all(),
        ]);
    }

    /** A purchasable variant whose SKU is exactly the query (and is the only such match). */
    private function exactSku(SearchTerms $search): ?ProductVariant
    {
        if (count($search->terms) !== 1) {
            return null;
        }

        $variants = ProductVariant::query()
            ->where('is_active', true)->where('sku', $search->raw)
            ->whereHas('product', fn ($p) => $p->published())
            ->with('product')->limit(2)->get();

        return $variants->count() === 1 ? $variants->first() : null;
    }

    /** Pieces to offer when there is nothing to show: featured first, then newest. */
    private function popular()
    {
        return Product::published()->purchasable()
            ->with(['variants', 'images'])
            ->orderByDesc('is_featured')->latest('published_at')
            ->limit(4)->get();
    }
}
