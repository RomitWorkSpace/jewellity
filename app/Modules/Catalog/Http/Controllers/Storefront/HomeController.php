<?php

namespace App\Modules\Catalog\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $card = ['variants', 'images'];

        $newArrivals = Product::published()->purchasable()
            ->with($card)
            ->latest('published_at')->latest('id')
            ->limit(config('storefront.home.new_arrivals'))
            ->get();

        $featured = Product::published()->purchasable()
            ->where('is_featured', true)
            ->with($card)
            ->latest('published_at')->latest('id')
            ->limit(config('storefront.home.featured'))
            ->get();

        $categories = Category::active()->roots()
            ->orderBy('sort_order')->orderBy('name')
            ->limit(8)
            ->get();

        return view('storefront.home', compact('newArrivals', 'featured', 'categories'));
    }
}
