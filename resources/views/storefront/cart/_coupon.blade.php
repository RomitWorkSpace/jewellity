{{-- Promo code box. $totals: OrderTotals, $couponError: ?string. Works as plain forms without JavaScript. --}}
@use('App\Support\Money')
@php $error = $couponError ?? session('coupon_error'); @endphp
<div class="mt-5">
    @if ($totals->coupon)
        <div class="flex items-center justify-between gap-3 rounded-xl bg-green-50 px-3.5 py-2.5 ring-1 ring-green-200">
            <p class="min-w-0 text-sm text-green-900">
                <span class="font-semibold break-all">{{ $totals->coupon->code }}</span> applied
                @if ($totals->couponSaving() > 0)<span class="block text-xs">You save {{ Money::format($totals->couponSaving()) }}</span>@endif
            </p>
            <form method="post" action="{{ url('/cart/coupon') }}" @submit.prevent="submit($event)" class="shrink-0">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-full px-2.5 py-1 text-xs font-semibold text-green-900 underline underline-offset-2 hover:bg-green-100" aria-label="Remove code {{ $totals->coupon->code }}">Remove</button>
            </form>
        </div>
    @else
        <form method="post" action="{{ url('/cart/coupon') }}" @submit.prevent="submit($event)" class="flex gap-2">
            @csrf
            <label for="coupon-code" class="sr-only">Promo code</label>
            <input id="coupon-code" name="code" type="text" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Promo code"
                   class="min-w-0 flex-1 rounded-full border border-royal-200 bg-white px-4 py-2.5 text-sm uppercase placeholder:normal-case focus:border-royal-500 focus:outline-none focus:ring-2 focus:ring-royal-200"
                   @if ($error) aria-invalid="true" aria-describedby="coupon-error" @endif>
            <button type="submit" class="shrink-0 rounded-full border border-royal-900 px-5 py-2.5 text-sm font-semibold text-royal-900 transition hover:bg-royal-50">Apply</button>
        </form>
        @if ($error)
            <p id="coupon-error" role="alert" class="mt-2 text-sm text-red-700">{{ $error }}</p>
        @endif
    @endif
    @if ($totals->couponNotice)
        <p role="status" class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">{{ $totals->couponNotice }}</p>
    @endif
</div>
