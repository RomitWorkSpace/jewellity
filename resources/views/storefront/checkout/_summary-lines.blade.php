@use('App\Support\Money')
<ul class="mt-4 divide-y divide-royal-100">
    @foreach ($summary->lines as $line)
        <li class="flex gap-3 py-3">
            <div class="relative size-16 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-royal-100 to-gold-50 ring-1 ring-royal-900/5">
                @if ($line->image)<img src="{{ $line->image }}" alt="" class="size-full object-cover">@endif
                <span class="absolute -right-0 -top-0 flex size-5 items-center justify-center rounded-bl-lg bg-royal-900 text-[11px] font-semibold text-white">{{ $line->quantity }}</span>
            </div>
            <div class="min-w-0 flex-1 text-sm">
                <p class="line-clamp-2 font-medium text-royal-950">{{ $line->name }}</p>
                @if ($line->options)<p class="text-xs text-royal-800/70">{{ $line->options }}</p>@endif
            </div>
            <p class="shrink-0 text-sm font-semibold text-royal-900">{{ Money::format($line->total()) }}</p>
        </li>
    @endforeach
</ul>
<dl class="mt-3 space-y-2.5 border-t border-royal-100 pt-4 text-sm">
    <div class="flex justify-between"><dt class="text-royal-800/80">Subtotal</dt><dd class="font-medium text-royal-950">{{ Money::format($totals->subtotal + $totals->savings) }}</dd></div>
    @if ($totals->savings > 0)
        <div class="flex justify-between"><dt class="text-gold-800">You save</dt><dd class="font-semibold text-gold-800">− {{ Money::format($totals->savings) }}</dd></div>
    @endif
    @if ($totals->discount > 0)
        <div class="flex justify-between gap-3"><dt class="text-green-800">Discount <span class="break-all">({{ $totals->coupon->code }})</span></dt><dd class="shrink-0 font-semibold text-green-800">− {{ Money::format($totals->discount) }}</dd></div>
    @endif
    <div class="flex justify-between"><dt class="text-royal-800/80">Shipping</dt><dd class="{{ $totals->shippingIsFree() ? 'font-semibold text-green-800' : 'font-medium text-royal-950' }}">{{ $totals->shippingIsFree() ? 'Free' : Money::format($totals->shipping) }}</dd></div>
    <div class="flex items-baseline justify-between border-t border-royal-100 pt-3"><dt class="font-semibold text-royal-950">Total</dt><dd class="text-xl font-semibold text-royal-900">{{ Money::format($totals->total()) }}</dd></div>
</dl>
@if ($totals->coupon)<p class="mt-2 text-xs text-royal-800/70">Code {{ $totals->coupon->code }} · <a href="{{ url('/cart') }}" class="underline">change in your bag</a></p>@endif
<p class="mt-1 text-xs text-royal-800/60">Inclusive of all taxes{{ $totals->taxIncluded > 0 ? ' (includes GST of '.Money::format($totals->taxIncluded).')' : '' }}.</p>
