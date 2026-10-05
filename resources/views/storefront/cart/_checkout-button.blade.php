@props(['class' => ''])
@if (config('storefront.checkout_enabled'))
    <a href="{{ url('/checkout') }}" class="btn-gold w-full !py-3.5 {{ $class }}">Proceed to checkout <x-icon name="arrow-right" class="size-4" /></a>
    @if (config('checkout.require_login') && ! auth()->check())
        <p class="mt-2 text-center text-xs text-royal-800/60">You&rsquo;ll be asked to sign in or create an account first. Your bag stays safe.</p>
    @endif
@else
    <button type="button" disabled class="btn-gold w-full cursor-not-allowed !py-3.5 opacity-60 grayscale {{ $class }}">Proceed to checkout</button>
    <p class="mt-2 text-center text-xs text-royal-800/60">Online checkout is opening soon.</p>
@endif
