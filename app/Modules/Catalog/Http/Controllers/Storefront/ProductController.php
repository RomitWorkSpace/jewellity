<?php

namespace App\Modules\Catalog\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Support\CategoryTree;
use App\Modules\Catalog\Support\ProductPage;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const RELATED = 4;

    public function show(Request $request, string $slug): View
    {
        $product = Product::published()->purchasable()
            ->where('slug', $slug)
            ->with(['variants.attributeValues.attribute', 'images', 'categories'])
            ->firstOrFail();

        $page = new ProductPage($product, new CategoryTree);
        $requested = $request->query('variant');
        $initial = $page->initialVariant(is_string($requested) && ctype_digit($requested) ? (int) $requested : null);

        $options = $page->options();
        $images = $page->images();

        return view('storefront.product', [
            'product' => $product,
            'breadcrumbs' => $page->breadcrumbs(),
            'category' => collect($page->breadcrumbs())->slice(-2, 1)->first(),
            'images' => $images,
            'optionGroups' => $options['groups'],
            'initial' => $initial,
            'related' => $this->related($product, $page->primaryCategoryId()),
            'pageData' => [
                'variants' => $page->variantPayload(),
                'groups' => $options['groups'],
                'images' => $images,
                'initialVariantId' => $initial->id,
                'maxQty' => (int) config('cart.max_per_line'),
                'cartUrl' => route('cart.items.store', absolute: false),
                'slug' => $product->slug,
                'name' => $product->name,
                'url' => url('/product/'.$product->slug),
                'recent' => [
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'price' => Money::format($initial->price),
                    'image' => $images[0]['url'] ?? null,
                ],
            ],
            'seo' => [
                'title' => $product->meta_title ?: $product->name.' – '.config('app.name'),
                'description' => $product->meta_description
                    ?: strip_tags($product->short_description ?: str($product->description)->limit(155)->toString())
                    ?: "Shop {$product->name} at ".config('app.name').'.',
                'image' => $images[0]['url'] ?? null,
                'canonical' => url('/product/'.$product->slug),
                'jsonLd' => $page->jsonLd(),
            ],
        ]);
    }

    /** Same-category products first, topped up with other published pieces. */
    private function related(Product $product, ?int $categoryId)
    {
        $base = fn () => Product::published()->purchasable()
            ->whereKeyNot($product->id)->with(['variants', 'images']);

        $related = $categoryId
            ? $base()->whereHas('categories', fn ($c) => $c->where('categories.id', $categoryId))
                ->latest('published_at')->limit(self::RELATED)->get()
            : collect();

        if ($related->count() < self::RELATED) {
            $related = $related->concat(
                $base()->whereNotIn('id', $related->pluck('id'))
                    ->orderByDesc('is_featured')->latest('published_at')
                    ->limit(self::RELATED - $related->count())->get()
            );
        }

        return $related;
    }
}
