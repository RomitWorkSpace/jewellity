{{-- Bag page body (swapped in place by the browser after each change). $summary: CartSummary --}}
@use('App\Support\Money')
@php $totals ??= \App\Modules\Orders\Pricing\OrderTotals::forBag($summary, auth()->user()); @endphp
<div x-data="{ barVisible: false }"
     x-init="const el = $refs.summary; if (el && 'IntersectionObserver' in window) new IntersectionObserver(([e]) => barVisible = !e.isIntersecting).observe(el)">

    @if (session('status'))
        <p role="status" class="mb-4 rounded-xl bg-royal-50 px-4 py-3 text-sm text-royal-900">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p role="alert" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</p>
    @endif
    @if ($summary->notices)
        <div class="mb-6">@include('storefront.cart._notices')</div>
    @endif

    @if ($summary->isEmpty())
        <div class="rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-16 text-center sm:py-24">
            <span class="mx-auto flex size-20 items-center justify-center rounded-full bg-white text-royal-700 shadow ring-1 ring-gold-300/60">
                <x-icon name="bag" class="size-9" />
            </span>
            <h2 class="mt-6 font-serif text-3xl font-semibold text-royal-950">Your bag is empty</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-royal-800/70">Pieces you add will wait for you here. Find something that sparkles.</p>
            <a href="{{ url('/shop?sort=newest') }}" class="btn-gold mt-8">Start shopping <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    @else
        <div class="grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-start lg:gap-12">
            <section aria-label="Items in your bag">
                <ul class="divide-y divide-royal-100 border-y border-royal-100">
                    @foreach ($summary->lines as $line)
                        @include('storefront.cart._line', ['line' => $line, 'compact' => false])
                    @endforeach
                </ul>
                <a href="{{ url('/shop') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-royal-700 hover:text-royal-500">
                    <x-icon name="chevron-left" class="size-4" /> Continue shopping
                </a>
            </section>

            <aside x-ref="summary" aria-label="Order summary" class="rounded-2xl border border-royal-100 bg-white p-5 shadow-sm sm:p-6 lg:sticky lg:top-40">
                <h2 class="font-serif text-xl font-semibold text-royal-950">Order summary</h2>
                @include('storefront.cart._shipping-progress', ['totals' => $totals])

                @include('storefront.cart._coupon')

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-royal-800/80">Subtotal ({{ $summary->count() }} {{ \Illuminate\Support\Str::plural('item', $summary->count()) }})</dt><dd class="font-medium text-royal-950">{{ Money::format($totals->subtotal + $totals->savings) }}</dd></div>
                    @if ($totals->savings > 0)
                        <div class="flex justify-between"><dt class="text-gold-800">You save</dt><dd class="font-semibold text-gold-800">− {{ Money::format($totals->savings) }}</dd></div>
                    @endif
                    @if ($totals->discount > 0)
                        <div class="flex justify-between gap-3"><dt class="text-green-800">Discount <span class="break-all">({{ $totals->coupon->code }})</span></dt><dd class="shrink-0 font-semibold text-green-800">− {{ Money::format($totals->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-royal-800/80">Shipping</dt>
                        <dd class="{{ $totals->shippingIsFree() ? 'font-semibold text-green-800' : 'font-medium text-royal-950' }}">{{ $totals->shippingIsFree() ? 'Free' : Money::format($totals->shipping) }}</dd>
                    </div>
                </dl>
                <div class="mt-5 flex items-baseline justify-between border-t border-royal-100 pt-5">
                    <span class="font-semibold text-royal-950">Total</span>
                    <span class="text-2xl font-semibold text-royal-900">{{ Money::format($totals->total()) }}</span>
                </div>
                <p class="mt-1 text-xs text-royal-800/60">Inclusive of all taxes{{ $totals->taxIncluded > 0 ? ' (includes GST of '.Money::format($totals->taxIncluded).')' : '' }}.</p>

                <div class="mt-6">@include('storefront.cart._checkout-button')</div>

                <ul class="mt-6 space-y-2.5 border-t border-royal-100 pt-5 text-sm text-royal-900">
                    @foreach (config('storefront.pdp.perks') as $perk)
                        <li class="flex items-center gap-3"><x-icon :name="$perk['icon']" class="size-5 shrink-0 text-gold-700" /> {{ $perk['text'] }}</li>
                    @endforeach
                </ul>
            </aside>
        </div>

        {{-- Sticky checkout bar for phones: shown while the summary is off-screen. --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-royal-100 bg-ivory/95 px-4 py-3 shadow-[0_-8px_24px_-12px_rgba(31,11,54,0.3)] backdrop-blur transition-transform duration-300 lg:hidden"
             :class="barVisible ? 'translate-y-0' : 'translate-y-full'" :aria-hidden="!barVisible" :inert="!barVisible">
            <div class="mx-auto flex max-w-xl items-center gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-royal-800/70">Total{{ $totals->shippingIsFree() ? ' · free shipping' : ' · incl. ₹'.($totals->shipping / 100).' shipping' }}</p>
                    <p class="text-lg font-semibold leading-tight text-royal-900">{{ Money::format($totals->total()) }}</p>
                </div>
                @if (config('storefront.checkout_enabled'))
                    <a href="{{ url('/checkout') }}" class="btn-gold ml-auto !px-7 !py-3">Checkout</a>
                @else
                    <button type="button" class="btn-gold ml-auto !px-6 !py-3" @click="$refs.summary.scrollIntoView({ behavior: 'smooth', block: 'center' })">View summary</button>
                @endif
            </div>
        </div>
    @endif
</div>
