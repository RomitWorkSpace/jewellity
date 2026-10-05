{{-- Free-shipping nudge. $totals: OrderTotals --}}
@use('App\Support\Money')
@if ($totals->shippingIsFree())
    <p class="flex items-center gap-2 rounded-xl bg-green-50 px-3.5 py-2.5 text-sm font-medium text-green-900">
        <x-icon name="check" class="size-4 shrink-0" /> You&rsquo;ve unlocked free shipping
    </p>
@else
    <div class="rounded-xl bg-gold-50 px-3.5 py-3 ring-1 ring-gold-200/70">
        <p class="text-sm text-gold-900">Add <strong>{{ Money::format($totals->freeShippingRemaining) }}</strong> more for <strong>free shipping</strong></p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gold-200/70" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $totals->freeShippingProgress }}" aria-label="Progress to free shipping">
            <div class="h-full rounded-full bg-gold-gradient transition-all duration-500" style="width: {{ $totals->freeShippingProgress }}%"></div>
        </div>
    </div>
@endif
