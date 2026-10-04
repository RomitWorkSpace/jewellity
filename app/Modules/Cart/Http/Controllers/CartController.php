<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /** The bag page. */
    public function show(CartService $cart): View
    {
        $summary = $cart->summary();

        return view('storefront.cart', [
            'summary' => $summary,
            'suggestions' => $this->suggestions(array_keys($cart->lines())),
            'seo' => ['title' => 'Shopping bag – '.config('app.name')],
        ]);
    }

    /** HTML for the slide-in mini bag. */
    public function mini(CartService $cart): View
    {
        return view('storefront.cart._mini', ['summary' => $cart->summary()]);
    }

    public function store(Request $request, CartService $cart): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('cart.max_per_line')],
        ]);

        $variant = $cart->purchasable($data['variant_id']);

        if (! $variant) {
            throw ValidationException::withMessages(['variant_id' => 'This item is no longer available.']);
        }

        $lineQuantity = $cart->add($variant, $data['quantity']);

        return response()->json([
            'message' => 'Added to your bag',
            'count' => $cart->count(),
            'line_quantity' => $lineQuantity,
        ], 201);
    }

    public function update(Request $request, CartService $cart, int $variant): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:'.config('cart.max_per_line')]]);

        $cart->update($variant, $data['quantity']);

        return $this->respond($request, $cart);
    }

    public function destroy(Request $request, CartService $cart, int $variant): JsonResponse|RedirectResponse
    {
        $cart->remove($variant);

        return $this->respond($request, $cart, 'Item removed');
    }

    /** JSON (with re-rendered HTML) for the in-page experience; a redirect when JavaScript is off. */
    private function respond(Request $request, CartService $cart, ?string $message = null): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect('/cart')->with('status', $message);
        }

        $summary = $cart->summary();
        $fragment = $request->input('view') === 'mini' ? 'storefront.cart._mini' : 'storefront.cart._content';

        return response()->json([
            'count' => $summary->count(),
            'message' => $message,
            'html' => view($fragment, ['summary' => $summary])->render(),
        ]);
    }

    /** "You may also like" for the empty / bottom of the bag: published pieces not already in it. */
    private function suggestions(array $variantIds)
    {
        $inBag = $variantIds
            ? Product::query()->whereHas('variants', fn ($v) => $v->whereIn('id', $variantIds))->pluck('id')
            : collect();

        return Product::published()->purchasable()
            ->whereNotIn('id', $inBag)
            ->with(['variants', 'images'])
            ->orderByDesc('is_featured')->latest('published_at')
            ->limit(4)->get();
    }
}
