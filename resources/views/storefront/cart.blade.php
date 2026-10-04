<x-layouts.storefront>
    <x-slot:seo>
        <x-seo :title="$seo['title']" :noindex="true" />
    </x-slot:seo>

    <div x-data="bag()" class="container-x pb-24 pt-6 sm:pt-10 lg:pb-16">
        <x-breadcrumbs :items="[['name' => 'Home', 'url' => url('/')], ['name' => 'Shopping bag', 'url' => url('/cart')]]" />
        <div class="mt-4 flex items-end justify-between gap-4">
            <h1 class="font-serif text-3xl font-semibold text-royal-950 sm:text-4xl">Shopping bag</h1>
            <p class="text-sm text-royal-800/70" x-text="$store.cart.count ? $store.cart.count + ($store.cart.count === 1 ? ' item' : ' items') : ''">{{ $summary->count() ? $summary->count().' '.\Illuminate\Support\Str::plural('item', $summary->count()) : '' }}</p>
        </div>

        <div class="mt-6 transition-opacity" x-ref="root" :class="busy && 'pointer-events-none opacity-60'" :aria-busy="busy">
            @include('storefront.cart._content')
        </div>

        @if ($suggestions->isNotEmpty())
            <section class="pt-14 sm:pt-20" aria-labelledby="bag-suggestions">
                <x-section-heading id="bag-suggestions" eyebrow="Don't miss" title="You may also like" />
                <div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                    @foreach ($suggestions as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.storefront>
