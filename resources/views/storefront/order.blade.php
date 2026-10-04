@use('App\Support\Money')
@use('App\Modules\Orders\Enums\OrderStatus')
@php
    $status = $order->status;
    $confirmed = $status->isConfirmed();
    $pending = $status === OrderStatus::PendingPayment;
    $holdUntil = $order->created_at->copy()->addMinutes((int) config('checkout.pending_order_minutes'));
    $steps = [['Order placed', [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered]], ['Preparing', [OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered]], ['Shipped', [OrderStatus::Shipped, OrderStatus::Delivered]], ['Delivered', [OrderStatus::Delivered]]];
    $payment = $order->payments->firstWhere('status', 'paid');
@endphp
<x-layouts.storefront>
    <x-slot:seo><x-seo :title="$seo['title']" :noindex="true" /></x-slot:seo>

    <div class="container-x pb-16 pt-6 sm:pt-10"
         x-data="orderPage({ payUrl: @js($order->signedUrl('orders.pay')), verifyUrl: @js(route('checkout.verify')) })">

        @auth
            @if ($order->user_id === auth()->id())
                <a href="{{ route('account.orders') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-royal-700 hover:text-royal-500"><x-icon name="chevron-left" class="size-4" /> My orders</a>
            @endif
        @endauth

        {{-- Status banner --}}
        @if ($confirmed)
            <section class="rounded-3xl bg-gradient-to-br from-royal-900 via-royal-800 to-royal-700 px-6 py-8 text-center text-white sm:py-12" aria-live="polite">
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-gold-gradient text-royal-950 shadow-lg shadow-gold-500/30"><x-icon name="check" class="size-8" /></span>
                <h1 class="mt-5 font-serif text-3xl font-semibold sm:text-4xl">Thank you, {{ \Illuminate\Support\Str::of($order->ship_name)->before(' ') }}!</h1>
                <p class="mt-2 text-royal-100/90">Your order <strong class="text-gold-200">{{ $order->number }}</strong> is confirmed.</p>
                <p class="mt-1 text-sm text-royal-200/80">A confirmation is on its way to {{ $order->email }}.</p>
            </section>
        @elseif ($pending)
            <section class="rounded-3xl border border-amber-300/70 bg-amber-50 px-6 py-7 sm:px-8" role="status">
                <h1 class="font-serif text-2xl font-semibold text-amber-950 sm:text-3xl">Complete your payment</h1>
                <p class="mt-1.5 text-sm text-amber-900">Order <strong>{{ $order->number }}</strong> is waiting for payment. We are holding your items until about <strong>{{ $holdUntil->format('g:i A') }}</strong>.</p>
                <p class="mt-1 text-xs text-amber-900/80">If you have already paid, this page will update shortly. There is no need to pay again.</p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="button" @click="payNow()" :disabled="busy" class="btn-gold !py-3 disabled:opacity-60"><span x-text="busy ? 'Please wait…' : 'Pay {{ Money::format($order->total) }} now'">Pay {{ Money::format($order->total) }} now</span></button>
                    <form method="post" action="{{ $order->signedUrl('orders.cancel') }}" onsubmit="return confirm('Cancel this order? Your items will go back to your bag.')">@csrf
                        <button type="submit" class="rounded-full border border-amber-400 px-6 py-3 text-sm font-semibold text-amber-950 hover:bg-amber-100">Cancel order</button>
                    </form>
                </div>
                <p x-cloak x-show="message" x-text="message" role="alert" class="mt-4 text-sm font-medium text-red-700"></p>
            </section>
        @else
            <section class="rounded-3xl border border-royal-200 bg-royal-50 px-6 py-8 text-center">
                <h1 class="font-serif text-2xl font-semibold text-royal-950 sm:text-3xl">This order was {{ $status === OrderStatus::Expired ? 'not paid in time' : 'cancelled' }}</h1>
                <p class="mt-2 text-sm text-royal-800/80">Order {{ $order->number }}. Nothing was charged.</p>
                <a href="{{ url('/shop') }}" class="btn-gold mt-5">Continue shopping</a>
            </section>
        @endif

        @if ($order->needs_attention && $confirmed)
            <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">We are checking a detail of this order and will email you shortly if anything changes.</p>
        @endif

        {{-- Progress --}}
        @if ($confirmed)
            <ol class="mx-auto mt-8 grid max-w-3xl grid-cols-4 gap-2 text-center" aria-label="Order progress">
                @foreach ($steps as [$label, $reached])
                    @php $done = in_array($status, $reached, true); @endphp
                    <li class="flex flex-col items-center gap-2 text-xs sm:text-sm">
                        <span class="flex size-8 items-center justify-center rounded-full {{ $done ? 'bg-gold-gradient text-royal-950' : 'bg-royal-100 text-royal-400' }}"><x-icon name="check" class="size-4" /></span>
                        <span class="{{ $done ? 'font-semibold text-royal-950' : 'text-royal-800/60' }}">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start lg:gap-12">
            <section aria-labelledby="items-h">
                <h2 id="items-h" class="font-serif text-xl font-semibold text-royal-950">{{ $order->itemCount() }} {{ \Illuminate\Support\Str::plural('item', $order->itemCount()) }}</h2>
                <ul class="mt-3 divide-y divide-royal-100 border-y border-royal-100">
                    @foreach ($order->items as $item)
                        <li class="flex gap-4 py-4">
                            <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-royal-100 to-gold-50 ring-1 ring-royal-900/5 sm:size-24">
                                @if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-royal-950">{{ $item->name }}</p>
                                @if ($item->options)<p class="mt-0.5 text-xs text-royal-800/70">{{ $item->options }}</p>@endif
                                <p class="mt-0.5 text-xs text-royal-800/50">SKU {{ $item->sku }}</p>
                                <p class="mt-2 text-sm text-royal-800/80">Qty {{ $item->quantity }} × {{ Money::format($item->unit_price) }}</p>
                            </div>
                            <p class="shrink-0 font-semibold text-royal-900">{{ Money::format($item->line_total) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            <aside class="space-y-5" aria-label="Order details">
                <section class="rounded-2xl border border-royal-100 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold text-royal-950">Summary</h2>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-royal-800/80">Order</dt><dd class="font-medium text-royal-950">{{ $order->number }}</dd></div>
                        <div class="flex justify-between"><dt class="text-royal-800/80">Placed</dt><dd class="text-royal-950">{{ $order->created_at->format('j M Y, g:i A') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-royal-800/80">Status</dt><dd class="font-medium text-royal-950">{{ $status->label() }}</dd></div>
                        <div class="flex justify-between border-t border-royal-100 pt-2.5"><dt class="text-royal-800/80">Subtotal</dt><dd>{{ Money::format($order->subtotal + $order->savings) }}</dd></div>
                        @if ($order->savings > 0)<div class="flex justify-between text-gold-800"><dt>You saved</dt><dd class="font-semibold">− {{ Money::format($order->savings) }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-royal-800/80">Shipping</dt><dd class="{{ $order->shipping === 0 ? 'font-semibold text-green-800' : '' }}">{{ $order->shipping === 0 ? 'Free' : Money::format($order->shipping) }}</dd></div>
                        <div class="flex items-baseline justify-between border-t border-royal-100 pt-2.5"><dt class="font-semibold text-royal-950">Total</dt><dd class="text-xl font-semibold text-royal-900">{{ Money::format($order->total) }}</dd></div>
                    </dl>
                    <p class="mt-1 text-xs text-royal-800/60">Inclusive of all taxes{{ $order->tax_included > 0 ? ' (includes GST of '.Money::format($order->tax_included).')' : '' }}.</p>
                    @if ($payment)<p class="mt-3 text-xs text-royal-800/70">Paid {{ $payment->paid_at?->format('j M Y, g:i A') }}{{ $payment->method ? ' via '.\Illuminate\Support\Str::upper($payment->method) : '' }} · Ref {{ $payment->provider_payment_id }}</p>@endif
                </section>

                <section class="rounded-2xl border border-royal-100 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold text-royal-950">Delivering to</h2>
                    <p class="mt-3 text-sm font-medium text-royal-950">{{ $order->ship_name }} · {{ $order->ship_phone }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-royal-800/80">{{ $order->addressText() }}</p>
                    @if ($order->customer_note)<p class="mt-3 border-t border-royal-100 pt-3 text-xs text-royal-800/70"><span class="font-semibold">Your note:</span> {{ $order->customer_note }}</p>@endif
                </section>

                <p class="text-center text-xs text-royal-800/60">Questions about this order? Quote <strong>{{ $order->number }}</strong> when you contact us.</p>
            </aside>
        </div>
    </div>
</x-layouts.storefront>
