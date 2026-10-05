{{-- Slide-in mini bag body. $summary: CartSummary --}}
@use('App\Support\Money')
@php $totals ??= \App\Modules\Orders\Pricing\OrderTotals::forBag($summary, auth()->user()); @endphp
<div class="flex h-full min-h-0 flex-col">
    @if ($summary->notices)
        <div class="px-5 pt-4">@include('storefront.cart._notices')</div>
    @endif

    @if ($summary->isEmpty())
        <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
            <span class="flex size-16 items-center justify-center rounded-full bg-royal-50 text-royal-700 ring-1 ring-gold-300/60"><x-icon name="bag" class="size-7" /></span>
            <p class="mt-5 font-serif text-xl font-semibold text-royal-950">Your bag is empty</p>
            <p class="mt-1 text-sm text-royal-800/70">Add a piece you love to see it here.</p>
            <a href="{{ url('/shop?sort=newest') }}" class="btn-gold mt-6" @click="$store.ui.miniCart = false">Start shopping</a>
        </div>
    @else
        <ul class="min-h-0 flex-1 divide-y divide-royal-100 overflow-y-auto px-5">
            @foreach ($summary->lines as $line)
                @include('storefront.cart._line', ['line' => $line, 'compact' => true])
            @endforeach
        </ul>

        <div class="border-t border-royal-100 bg-white px-5 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4">
            <div class="flex items-baseline justify-between">
                <span class="text-sm font-medium text-royal-950">Subtotal</span>
                <span class="text-xl font-semibold text-royal-900">{{ Money::format($totals->net()) }}</span>
            </div>
            @if ($totals->discount > 0)
                <p class="mt-0.5 flex justify-between text-xs font-medium text-green-800"><span>Code <span class="break-all">{{ $totals->coupon->code }}</span></span><span>− {{ Money::format($totals->discount) }}</span></p>
            @endif
            @if ($summary->savings() > 0)
                <p class="mt-0.5 text-right text-xs font-medium text-gold-800">You save {{ Money::format($summary->savings()) }}</p>
            @endif
            <p class="mt-1 text-xs text-royal-800/60">
                @if ($totals->shippingIsFree()) Free shipping &middot; @else Shipping {{ Money::format($totals->shipping) }} &middot; add {{ Money::format($totals->freeShippingRemaining) }} for free shipping &middot; @endif
                GST included.
            </p>
            <div class="mt-4 grid gap-2.5">
                <a href="{{ url('/cart') }}" class="inline-flex items-center justify-center rounded-full border border-royal-900 px-7 py-3 text-sm font-semibold text-royal-900 transition hover:bg-royal-50">View bag</a>
                @if (config('storefront.checkout_enabled'))
                    <a href="{{ url('/checkout') }}" class="btn-gold !py-3">Checkout</a>
                @endif
            </div>
        </div>
    @endif
</div>
