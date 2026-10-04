@php
    $hasFacets = $facets->isNotEmpty() || ($priceBounds['min'] !== null);
    $pillBase = 'inline-flex shrink-0 items-center rounded-full border px-4 py-2 text-sm font-medium transition';
@endphp
<x-layouts.storefront>
    <x-slot:seo>
        <x-seo :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']"
               :noindex="$seo['noindex']" :prev="$seo['prev']" :next="$seo['next']" :json-ld="$seo['jsonLd']" />
    </x-slot:seo>

    <div x-data x-effect="document.body.classList.toggle('overflow-hidden', $store.ui.filters)"
         @keydown.escape.window="$store.ui.filters = false">

        {{-- ============================== PAGE HEADER ============================== --}}
        <section class="border-b border-royal-100 bg-gradient-to-b from-royal-50 to-ivory">
            <div class="container-x py-6 sm:py-10">
                <x-breadcrumbs :items="$breadcrumbs" />

                <div class="mt-4 flex flex-wrap items-end justify-between gap-x-8 gap-y-2">
                    <div class="max-w-2xl">
                        <h1 class="font-serif text-3xl font-semibold text-royal-950 sm:text-4xl lg:text-5xl">{{ $heading }}</h1>
                        @if ($description)
                            <p class="mt-3 text-sm leading-relaxed text-royal-800/80 sm:text-base">{{ $description }}</p>
                        @endif
                    </div>
                    <p class="text-sm text-royal-800/70">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('piece', $products->total()) }}</p>
                </div>

                @if ($children->isNotEmpty())
                    <nav aria-label="Sub-categories" class="-mx-4 mt-6 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
                        <ul class="flex gap-2 sm:flex-wrap">
                            @if ($category)
                                <li><span aria-current="page" class="{{ $pillBase }} border-transparent bg-royal-900 text-white">All {{ $category->name }}</span></li>
                            @endif
                            @foreach ($children as $child)
                                <li><a href="{{ url('/category/'.$child->slug) }}" class="{{ $pillBase }} border-royal-200 bg-white text-royal-900 hover:border-gold-400 hover:bg-gold-50">{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </div>
        </section>

        <div class="container-x py-8 sm:py-10">
            {{-- ============================== TOOLBAR ============================== --}}
            <div class="flex items-center justify-between gap-3">
                <button type="button" @click="$store.ui.filters = true" aria-controls="filter-panel" :aria-expanded="$store.ui.filters"
                        @class(['inline-flex items-center gap-2 rounded-full border border-royal-200 bg-white px-4 py-2.5 text-sm font-medium text-royal-900 transition hover:border-gold-400 lg:hidden', 'hidden' => ! $hasFacets])>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M7 12h10M10 18h4"/></svg>
                    Filters
                    @if ($filters->activeValueCount())
                        <span class="flex size-5 items-center justify-center rounded-full bg-gold-gradient text-[11px] font-bold text-royal-950">{{ $filters->activeValueCount() }}</span>
                    @endif
                </button>

                <div class="ml-auto flex items-center gap-2">
                    <label for="sort" class="hidden text-sm text-royal-800/70 sm:block">Sort by</label>
                    <select id="sort" name="sort" form="filters" @change="$el.form.requestSubmit()"
                            class="rounded-full border border-royal-200 bg-white py-2.5 pl-4 pr-9 text-sm font-medium text-royal-900 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-300/50">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($filters->sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if ($chips)
                <ul class="mt-4 flex flex-wrap items-center gap-2" aria-label="Active filters">
                    @foreach ($chips as $chip)
                        <li>
                            <a href="{{ $chip['url'] }}" class="group inline-flex items-center gap-1.5 rounded-full bg-royal-900 py-1.5 pl-3.5 pr-2.5 text-xs font-medium text-white transition hover:bg-royal-700" aria-label="Remove filter {{ $chip['label'] }}">
                                {{ $chip['label'] }} <x-icon name="close" class="size-3.5 text-gold-300" />
                            </a>
                        </li>
                    @endforeach
                    <li><a href="{{ $baseUrl.($persist ? '?'.http_build_query($persist) : '') }}" class="px-2 text-xs font-semibold text-royal-700 underline underline-offset-2 hover:text-royal-500">Clear all</a></li>
                </ul>
            @endif

            <div class="mt-6 lg:grid lg:grid-cols-[16.5rem_1fr] lg:items-start lg:gap-10">

                {{-- ============================== FILTERS ============================== --}}
                @if ($hasFacets)
                    <div x-cloak @click="$store.ui.filters = false" aria-hidden="true"
                         class="fixed inset-0 z-50 bg-royal-950/60 backdrop-blur-[2px] transition-opacity duration-300 ease-in-out lg:hidden"
                         :class="$store.ui.filters ? 'opacity-100' : 'pointer-events-none opacity-0'"></div>

                    <aside id="filter-panel" aria-label="Filters" data-cloak-lt-lg x-init="$el.removeAttribute('data-cloak-lt-lg')"
                           class="fixed inset-y-0 left-0 z-50 flex w-[88vw] max-w-sm flex-col bg-ivory shadow-2xl transition-[translate,visibility] duration-300 ease-in-out lg:visible lg:sticky lg:top-40 lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:rounded-2xl lg:border lg:border-royal-100 lg:bg-white lg:shadow-none"
                           :class="$store.ui.filters ? 'visible translate-x-0' : 'invisible -translate-x-full'">

                        <div class="flex items-center justify-between border-b border-royal-100 px-5 py-4">
                            <h2 class="font-serif text-xl font-semibold text-royal-950">Filters</h2>
                            <button type="button" @click="$store.ui.filters = false" class="-mr-2 rounded-full p-2 text-royal-700 hover:bg-royal-50 lg:hidden" aria-label="Close filters">
                                <x-icon name="close" class="size-5" />
                            </button>
                        </div>

                        <form id="filters" method="get" action="{{ $baseUrl }}" class="flex min-h-0 flex-1 flex-col"
                              @submit="$el.querySelectorAll('input[type=number]').forEach(i => { if (! i.value) i.disabled = true })">
                            @foreach ($persist as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                            <div class="flex-1 divide-y divide-royal-100 overflow-y-auto px-5">
                                {{-- Price --}}
                                @if ($priceBounds['min'] !== null)
                                    <details open class="group py-4">
                                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-royal-950">
                                            Price <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" />
                                        </summary>
                                        <div class="mt-3 flex items-center gap-2">
                                            <label class="sr-only" for="min_price">Minimum price</label>
                                            <input id="min_price" name="min_price" type="number" min="0" step="1" inputmode="numeric"
                                                   value="{{ $filters->minPriceInput() }}" placeholder="{{ intdiv($priceBounds['min'], 100) }}"
                                                   class="w-full rounded-lg border border-royal-200 px-3 py-2 text-sm placeholder:text-royal-300 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-300/50">
                                            <span class="text-royal-400" aria-hidden="true">–</span>
                                            <label class="sr-only" for="max_price">Maximum price</label>
                                            <input id="max_price" name="max_price" type="number" min="0" step="1" inputmode="numeric"
                                                   value="{{ $filters->maxPriceInput() }}" placeholder="{{ (int) ceil($priceBounds['max'] / 100) }}"
                                                   class="w-full rounded-lg border border-royal-200 px-3 py-2 text-sm placeholder:text-royal-300 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-300/50">
                                        </div>
                                        <p class="mt-2 text-xs text-royal-800/60">Amounts in {{ config('catalog.currency') }}</p>
                                    </details>
                                @endif

                                {{-- Availability --}}
                                <details open class="group py-4">
                                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-royal-950">
                                        Availability <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" />
                                    </summary>
                                    <label class="mt-3 flex cursor-pointer items-center gap-3 text-sm text-royal-900">
                                        <input type="checkbox" name="in_stock" value="1" @checked($filters->inStock)
                                               @change="if (window.matchMedia('(min-width: 1024px)').matches) $el.form.requestSubmit()"
                                               class="size-4 rounded border-royal-300 accent-royal-700">
                                        In stock only
                                    </label>
                                </details>

                                {{-- Attribute facets (Colour, Size, …) --}}
                                @foreach ($facets as $attrSlug => $values)
                                    <details open class="group py-4">
                                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-royal-950">
                                            {{ $values->first()->attribute }} <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" />
                                        </summary>
                                        <ul class="mt-3 space-y-2.5">
                                            @foreach ($values as $v)
                                                <li>
                                                    <label class="flex cursor-pointer items-center gap-3 text-sm text-royal-900">
                                                        <input type="checkbox" name="attr[{{ $attrSlug }}][]" value="{{ $v->slug }}"
                                                               @checked(in_array($v->slug, $filters->attrs[$attrSlug] ?? [], true))
                                                               @change="if (window.matchMedia('(min-width: 1024px)').matches) $el.form.requestSubmit()"
                                                               class="size-4 rounded border-royal-300 accent-royal-700">
                                                        {{ $v->value }}
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-3 border-t border-royal-100 px-5 py-4">
                                <button type="submit" class="btn-gold flex-1 !py-2.5">Apply</button>
                                @if ($filters->isFiltered())
                                    <a href="{{ $baseUrl.($persist ? '?'.http_build_query($persist) : '') }}" class="text-sm font-semibold text-royal-700 underline underline-offset-2">Clear</a>
                                @endif
                            </div>
                        </form>
                    </aside>
                @else
                    {{-- No facets: the sort select still needs a form to submit. --}}
                    <form id="filters" method="get" action="{{ $baseUrl }}" class="hidden">@foreach ($persist as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach</form>
                @endif

                {{-- ============================== PRODUCTS ============================== --}}
                <div class="min-w-0">
                    @if ($products->isNotEmpty())
                        <div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-3">
                            @foreach ($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                        {{ $products->links('pagination.storefront') }}
                    @elseif ($filters->isFiltered())
                        <div class="rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-16 text-center">
                            <x-icon name="search" class="mx-auto size-10 text-gold-500" />
                            <p class="mt-4 font-serif text-2xl text-royal-900">No pieces match these filters</p>
                            <p class="mx-auto mt-2 max-w-md text-sm text-royal-800/70">Try removing a filter or widening the price range.</p>
                            <a href="{{ $baseUrl.($persist ? '?'.http_build_query($persist) : '') }}" class="btn-gold mt-6">Clear all filters</a>
                        </div>
                    @elseif (isset($searchQuery))
                        <div class="rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-14 text-center sm:py-16">
                            <x-icon name="search" class="mx-auto size-10 text-gold-500" />
                            <p class="mt-4 font-serif text-2xl text-royal-900">No results for “{{ $searchQuery }}”</p>
                            <ul class="mx-auto mt-4 max-w-sm space-y-1.5 text-left text-sm text-royal-800/80">
                                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-600" /> Check the spelling</li>
                                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-600" /> Try fewer or more general words, like “earrings”</li>
                                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-600" /> Search by colour or product type</li>
                            </ul>
                            <a href="{{ url('/shop') }}" class="btn-gold mt-6">Browse all jewellery</a>
                        </div>
                    @else
                        <div class="rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-16 text-center">
                            <x-icon name="sparkle" class="mx-auto size-10 text-gold-500" />
                            <p class="mt-4 font-serif text-2xl text-royal-900">New pieces are on their way</p>
                            <p class="mx-auto mt-2 max-w-md text-sm text-royal-800/70">Nothing here just yet. Explore the rest of the collection in the meantime.</p>
                            <a href="{{ url('/shop') }}" class="btn-gold mt-6">Browse all jewellery</a>
                        </div>
                    @endif
                </div>
            </div>

            @if (! empty($noResultsExtras))
                @if ($noResultsExtras['categories']->isNotEmpty())
                    <nav aria-label="Browse categories" class="mt-12">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700">Browse by category</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($noResultsExtras['categories'] as $cat)
                                <li><a href="{{ url('/category/'.$cat->slug) }}" class="{{ $pillBase }} border-royal-200 bg-white text-royal-900 hover:border-gold-400 hover:bg-gold-50">{{ $cat->name }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
                @if ($noResultsExtras['suggestions']->isNotEmpty())
                    <section class="mt-14" aria-labelledby="popular-heading">
                        <x-section-heading id="popular-heading" eyebrow="Popular right now" title="You might like" />
                        <div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                            @foreach ($noResultsExtras['suggestions'] as $item)
                                <x-product-card :product="$item" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-layouts.storefront>
