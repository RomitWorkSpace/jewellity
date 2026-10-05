<x-layouts.storefront>
    <x-slot:seo>
        <x-seo :title="$seo['title']" :noindex="true" />
    </x-slot:seo>

    <section class="bg-gradient-to-b from-royal-50 to-ivory">
        <div class="container-x py-10 text-center sm:py-16">
            <x-icon name="search" class="mx-auto size-10 text-gold-600" />
            <h1 class="mt-4 font-serif text-3xl font-semibold text-royal-950 sm:text-4xl">What are you looking for?</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-royal-800/70">Search by name, colour, category or product code.</p>
            <form action="{{ url('/search') }}" method="get" role="search" class="mx-auto mt-6 flex max-w-xl gap-2">
                <label for="search-page-input" class="sr-only">Search jewellery</label>
                <input id="search-page-input" type="search" name="q" autofocus autocomplete="off" placeholder="Try “gold hoops” or “choker”"
                       class="min-w-0 flex-1 rounded-full border border-royal-200 bg-white px-5 py-3 text-sm placeholder:text-royal-400 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-300/50">
                <button type="submit" class="btn-gold shrink-0 !px-6">Search</button>
            </form>
        </div>
    </section>

    <div class="container-x pb-4 pt-10">
        @if ($categories->isNotEmpty())
            <nav aria-label="Browse categories">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700">Browse by category</p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($categories as $cat)
                        <li><a href="{{ url('/category/'.$cat->slug) }}" class="inline-flex items-center rounded-full border border-royal-200 bg-white px-4 py-2 text-sm font-medium text-royal-900 transition hover:border-gold-400 hover:bg-gold-50">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($suggestions->isNotEmpty())
            <section class="mt-14" aria-labelledby="popular-heading">
                <x-section-heading id="popular-heading" eyebrow="Popular right now" title="You might like" />
                <div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                    @foreach ($suggestions as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.storefront>
