<x-layouts.storefront>
    <x-slot:seo><x-seo :title="$seo['title']" :noindex="true" /></x-slot:seo>
    <div class="container-x py-8 sm:py-12">
        <div class="flex items-end justify-between gap-3">
            <h1 class="font-serif text-3xl font-semibold text-royal-950 sm:text-4xl">My wishlist</h1>
            @if ($products->isNotEmpty())<p class="text-sm text-royal-800/70">{{ $products->count() }} {{ \Illuminate\Support\Str::plural('piece', $products->count()) }}</p>@endif
        </div>

        @if (session('status'))
            <p role="status" class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-900 ring-1 ring-green-200">{{ session('status') }}</p>
        @endif

        @if ($products->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-16 text-center">
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-white text-royal-600 shadow ring-1 ring-gold-300/60"><x-icon name="heart" class="size-7" /></span>
                <p class="mt-5 font-serif text-2xl text-royal-900">Your wishlist is empty</p>
                <p class="mx-auto mt-2 max-w-sm text-sm text-royal-800/70">Tap the heart on any piece to save it here for later.</p>
                <a href="{{ url('/shop?sort=newest') }}" class="btn-gold mt-6">Discover new arrivals</a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <p class="mt-8 text-xs text-royal-800/60">Tap a filled heart to remove a piece. Items that are no longer on sale are hidden but stay saved.</p>
        @endif
    </div>
</x-layouts.storefront>
