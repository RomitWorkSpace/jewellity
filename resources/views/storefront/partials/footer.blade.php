@php $social = array_filter(config('storefront.social')); @endphp
<footer class="mt-24 bg-royal-950 text-royal-100">
    <div class="h-1 bg-gold-gradient"></div>
    <div class="container-x grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            <x-logo :dark="true" />
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-royal-200/80">
                Statement pieces and everyday favourites, designed to make every moment sparkle.
            </p>
            @if ($social)
                <ul class="mt-5 flex gap-3">
                    @foreach ($social as $name => $href)
                        <li><a href="{{ $href }}" rel="noopener" target="_blank" class="rounded-full border border-gold-400/40 px-4 py-1.5 text-xs font-medium capitalize text-gold-200 transition hover:border-gold-300 hover:bg-white/5">{{ $name }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <nav aria-label="Shop">
            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-300">Shop</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ url('/shop?sort=newest') }}" class="text-royal-200/80 transition hover:text-gold-200">New Arrivals</a></li>
                @foreach ($navCategories as $cat)
                    <li><a href="{{ url('/category/'.$cat->slug) }}" class="text-royal-200/80 transition hover:text-gold-200">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="Account">
            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-300">My account</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route(auth()->check() ? 'account' : 'login') }}" class="text-royal-200/80 transition hover:text-gold-200">{{ auth()->check() ? 'My account' : 'Sign in / Register' }}</a></li>
                <li><a href="{{ url('/wishlist') }}" class="text-royal-200/80 transition hover:text-gold-200">Wishlist</a></li>
                <li><a href="{{ url('/cart') }}" class="text-royal-200/80 transition hover:text-gold-200">Shopping bag</a></li>
            </ul>
        </nav>
    </div>
    <div class="border-t border-white/10">
        <p class="container-x py-5 text-center text-xs text-royal-300/70">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</footer>
