<x-layouts.storefront>
    <x-slot:seo>
        <x-seo
            :title="config('app.name').' – Artificial Jewellery for Every Occasion'"
            description="Shop earrings, necklaces, bracelets, bangles, rings and pendants. Statement pieces and everyday favourites, designed to sparkle."
            :json-ld="[
                '@context' => 'https://schema.org',
                '@graph' => [
                    ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
                    ['@type' => 'WebSite', 'name' => config('app.name'), 'url' => url('/')],
                ],
            ]" />
    </x-slot:seo>

    {{-- ============================== HERO ============================== --}}
    <section class="relative isolate overflow-hidden bg-royal-950 text-white">
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -left-32 top-0 size-[34rem] rounded-full bg-royal-600/40 blur-3xl"></div>
            <div class="absolute -right-24 bottom-0 size-[30rem] rounded-full bg-gold-500/20 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_70%_40%,rgba(132,73,198,0.35),transparent_60%)]"></div>
            <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-gold-400/60 to-transparent"></div>
        </div>

        <div class="container-x grid items-center gap-10 py-16 sm:py-20 lg:grid-cols-2 lg:gap-6 lg:py-28">
            <div class="text-center lg:text-left">
                <p class="inline-flex items-center gap-2 rounded-full border border-gold-400/40 bg-white/5 px-4 py-1.5 text-xs font-medium uppercase tracking-[0.22em] text-gold-200">
                    <x-icon name="sparkle" class="size-3.5 text-gold-400" /> The new collection
                </p>
                <h1 class="mt-6 font-serif text-4xl font-semibold leading-[1.1] sm:text-5xl lg:text-6xl xl:text-7xl">
                    Jewellery that <span class="text-gold-gradient pr-[0.12em] italic">tells your story</span>
                </h1>
                <p class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-royal-100/80 sm:text-lg lg:mx-0">
                    Earrings, necklaces, bangles and more — crafted to catch the light and priced to love, for every occasion.
                </p>
                <div class="mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start">
                    <a href="{{ url('/shop?sort=newest') }}" class="btn-gold w-full sm:w-auto">Shop new arrivals <x-icon name="arrow-right" class="size-4" /></a>
                    <a href="#categories" class="btn-outline-light w-full sm:w-auto">Browse categories</a>
                </div>
            </div>

            {{-- Decorative faceted gem --}}
            <div class="relative mx-auto w-full max-w-[22rem] sm:max-w-md lg:max-w-lg" aria-hidden="true">
                <div class="absolute inset-8 rounded-full bg-gold-400/20 blur-3xl"></div>
                <svg viewBox="0 0 400 400" class="relative animate-float drop-shadow-[0_20px_40px_rgba(200,150,31,0.35)]">
                    <defs>
                        <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fbf0b9"/><stop offset=".55" stop-color="#e3b232"/><stop offset="1" stop-color="#a8771a"/></linearGradient>
                        <linearGradient id="g2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4e08f"/><stop offset="1" stop-color="#c8961f"/></linearGradient>
                        <linearGradient id="g3" x1="1" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c8961f"/><stop offset="1" stop-color="#86591a"/></linearGradient>
                    </defs>
                    <polygon points="110,60 290,60 120,140 80,140" fill="url(#g2)" opacity=".0"/>
                    <polygon points="110,60 290,60 280,140 120,140" fill="url(#g1)"/>
                    <polygon points="110,60 120,140 40,140" fill="url(#g2)" opacity=".85"/>
                    <polygon points="290,60 360,140 280,140" fill="url(#g3)" opacity=".9"/>
                    <polygon points="40,140 120,140 200,355" fill="url(#g3)"/>
                    <polygon points="120,140 200,140 200,355" fill="url(#g2)"/>
                    <polygon points="200,140 280,140 200,355" fill="url(#g1)"/>
                    <polygon points="280,140 360,140 200,355" fill="url(#g3)" opacity=".92"/>
                    <g fill="none" stroke="#fff7d6" stroke-opacity=".55" stroke-width="1.2" stroke-linejoin="round">
                        <polygon points="110,60 290,60 360,140 200,355 40,140"/>
                        <line x1="40" y1="140" x2="360" y2="140"/>
                        <line x1="110" y1="60" x2="120" y2="140"/><line x1="290" y1="60" x2="280" y2="140"/>
                        <line x1="120" y1="140" x2="200" y2="355"/><line x1="280" y1="140" x2="200" y2="355"/><line x1="200" y1="140" x2="200" y2="355"/>
                    </g>
                    <polygon points="140,78 200,78 178,118" fill="#fff" opacity=".35"/>
                </svg>
                @foreach ([['left-[6%] top-[12%] size-6', '0s'], ['right-[4%] top-[28%] size-4', '.8s'], ['left-[14%] bottom-[16%] size-5', '1.6s'], ['right-[16%] bottom-[8%] size-6', '2.3s'], ['left-1/2 top-0 size-3', '1.2s']] as [$pos, $delay])
                    <svg viewBox="0 0 24 24" class="absolute {{ $pos }} animate-sparkle text-gold-200" style="animation-delay: {{ $delay }}" fill="currentColor"><path d="M12 0c.6 6.6 5.4 11.4 12 12-6.6.6-11.4 5.4-12 12-.6-6.6-5.4-11.4-12-12C6.6 11.4 11.4 6.6 12 0z"/></svg>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== PERKS ============================== --}}
    <section class="border-b border-royal-100 bg-white" aria-label="Why shop with us">
        <ul class="container-x grid grid-cols-2 gap-x-4 gap-y-6 py-8 lg:grid-cols-4">
            @foreach (config('storefront.perks') as $perk)
                <li class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-royal-50 text-royal-700 ring-1 ring-gold-300/60">
                        <x-icon :name="$perk['icon']" class="size-6" />
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-royal-950">{{ $perk['title'] }}</span>
                        <span class="mt-0.5 block text-xs leading-snug text-royal-800/70">{{ $perk['text'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ============================== CATEGORIES ============================== --}}
    @if ($categories->isNotEmpty())
        <section id="categories" class="container-x scroll-mt-32 pt-16 sm:pt-20" aria-labelledby="cat-heading">
            <x-section-heading id="cat-heading" eyebrow="Explore" title="Shop by category" />

            @php $catCols = in_array($categories->count(), [3, 6, 9]) ? 'lg:grid-cols-3' : 'lg:grid-cols-4'; @endphp
            <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 {{ $catCols }}">
                @foreach ($categories as $i => $cat)
                    @php
                        $bg = [
                            'from-royal-800 via-royal-700 to-royal-500',
                            'from-royal-950 via-royal-800 to-royal-600',
                            'from-gold-700 via-gold-500 to-gold-300',
                            'from-royal-900 via-royal-600 to-royal-400',
                        ][$i % 4];
                        $goldTile = $i % 4 === 2;
                    @endphp
                    <li>
                        <a href="{{ url('/category/'.$cat->slug) }}" class="group relative block aspect-[4/5] overflow-hidden rounded-2xl bg-gradient-to-br {{ $bg }} ring-1 ring-royal-900/10">
                            @if ($cat->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('catalog.images.disk'))->url($cat->image_path) }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-royal-950/80 via-royal-950/10 to-transparent"></div>
                            @else
                                <span class="absolute -right-4 -top-6 select-none font-serif text-[9rem] font-semibold leading-none {{ $goldTile ? 'text-white/25' : 'text-white/10' }} transition duration-700 group-hover:scale-110">{{ mb_strtoupper(mb_substr($cat->name, 0, 1)) }}</span>
                                <x-icon name="sparkle" class="absolute left-4 top-4 size-5 {{ $goldTile ? 'text-royal-950/60' : 'text-gold-300' }}" />
                            @endif
                            <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                <h3 class="font-serif text-xl font-semibold sm:text-2xl {{ $goldTile && ! $cat->image_path ? 'text-royal-950' : 'text-white' }}">{{ $cat->name }}</h3>
                                <span class="mt-1 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider {{ $goldTile && ! $cat->image_path ? 'text-royal-900' : 'text-gold-200' }}">
                                    Shop now <x-icon name="arrow-right" class="size-3.5 transition-transform duration-300 group-hover:translate-x-1" />
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ============================== NEW ARRIVALS ============================== --}}
    <section class="container-x pt-16 sm:pt-24" aria-labelledby="new-heading">
        <x-section-heading id="new-heading" eyebrow="Just in" title="New arrivals" :link="url('/shop?sort=newest')" link-label="View all" />

        @if ($newArrivals->isNotEmpty())
            <div class="mt-10 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                @foreach ($newArrivals as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @else
            <div class="mt-10 rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-16 text-center">
                <x-icon name="sparkle" class="mx-auto size-10 text-gold-500" />
                <p class="mt-4 font-serif text-2xl text-royal-900">A new collection is on its way</p>
                <p class="mx-auto mt-2 max-w-md text-sm text-royal-800/70">We are polishing the finishing touches. Check back very soon.</p>
            </div>
        @endif
    </section>

    {{-- ============================== BRAND BAND ============================== --}}
    <section class="container-x pt-16 sm:pt-24" aria-label="Our promise">
        <div class="relative isolate overflow-hidden rounded-3xl bg-royal-900 px-6 py-14 text-center text-white sm:px-12 sm:py-20">
            <div class="absolute inset-0 -z-10" aria-hidden="true">
                <div class="absolute -left-20 -top-20 size-80 rounded-full bg-royal-500/40 blur-3xl"></div>
                <div class="absolute -bottom-24 -right-16 size-80 rounded-full bg-gold-500/25 blur-3xl"></div>
            </div>
            <div class="absolute inset-3 -z-10 rounded-[1.25rem] border border-gold-400/40" aria-hidden="true"></div>
            <p class="text-xs font-medium uppercase tracking-[0.3em] text-gold-300">Our promise</p>
            <h2 class="mx-auto mt-4 max-w-2xl font-serif text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                Made to be noticed, <span class="text-gold-gradient pr-[0.12em] italic">designed to be loved</span>
            </h2>
            <p class="mx-auto mt-5 max-w-xl text-royal-100/80">From everyday studs to festive statement sets — find the piece that feels like you.</p>
            <a href="{{ url('/shop') }}" class="btn-gold mt-8">Explore the collection <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>

    {{-- ============================== FEATURED ============================== --}}
    @if ($featured->isNotEmpty())
        <section class="container-x pt-16 sm:pt-24" aria-labelledby="feat-heading">
            <x-section-heading id="feat-heading" eyebrow="Handpicked" title="Featured pieces" />
            <div class="mt-10 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.storefront>
