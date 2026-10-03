@php
    $maxNav = config('storefront.home.max_nav_categories');
    $navTop = $navCategories->take($maxNav);
    $announcement = config('storefront.announcement');
@endphp

@if ($announcement)
    <div class="bg-royal-950 text-center text-xs tracking-wide text-gold-200 sm:text-[13px]">
        <p class="container-x flex items-center justify-center gap-2 py-2">
            <x-icon name="sparkle" class="size-3.5 shrink-0 text-gold-400" /> {{ $announcement }}
        </p>
    </div>
@endif

<header x-data="{ scrolled: false, search: false }"
        x-init="scrolled = window.scrollY > 24"
        @scroll.window.passive="scrolled = window.scrollY > 24"
        @keydown.escape.window="search = false; $store.ui.mobileMenu = false"
        class="sticky top-0 z-40 border-b bg-ivory/95 backdrop-blur transition-shadow duration-300"
        :class="scrolled ? 'border-royal-100 shadow-[0_6px_24px_-12px_rgba(31,11,54,0.25)]' : 'border-transparent'">

    {{-- Main row --}}
    <div class="container-x grid grid-cols-[1fr_auto_1fr] items-center transition-all duration-300"
         :class="scrolled ? 'py-2' : 'py-3 lg:py-4'">

        {{-- Left: menu button (mobile) / search (desktop) --}}
        <div class="flex items-center gap-1">
            <button type="button" class="-ml-2 rounded-full p-2 text-royal-900 hover:bg-royal-50 lg:hidden"
                    @click="$store.ui.mobileMenu = true" aria-label="Open menu"
                    :aria-expanded="$store.ui.mobileMenu" aria-controls="mobile-menu">
                <x-icon name="menu" class="size-6" />
            </button>

            <form action="{{ url('/search') }}" method="get" role="search" class="hidden lg:block">
                <label for="desktop-search" class="sr-only">Search jewellery</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-royal-400" />
                    <input id="desktop-search" type="search" name="q" placeholder="Search jewellery…" autocomplete="off"
                           class="w-56 rounded-full border border-royal-100 bg-white py-2 pl-10 pr-4 text-sm text-royal-950 placeholder:text-royal-400 transition-all duration-300 focus:w-72 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-300/50 xl:w-64 xl:focus:w-80">
                </div>
            </form>
        </div>

        {{-- Centre: logo --}}
        <a href="{{ url('/') }}" aria-label="{{ config('app.name') }} home" class="justify-self-center transition-transform duration-300" :class="scrolled ? 'scale-90' : ''">
            <x-logo />
        </a>

        {{-- Right: actions --}}
        <div class="flex items-center justify-end gap-0.5 sm:gap-1">
            <button type="button" class="rounded-full p-2 text-royal-900 hover:bg-royal-50 lg:hidden"
                    @click="search = !search; $nextTick(() => search && $refs.mobileSearch.focus())"
                    aria-label="Search" :aria-expanded="search" aria-controls="mobile-search">
                <x-icon name="search" class="size-[22px]" />
            </button>
            <a href="{{ url('/account') }}" class="hidden rounded-full p-2 text-royal-900 hover:bg-royal-50 sm:block" aria-label="My account">
                <x-icon name="user" class="size-[22px]" />
            </a>
            <a href="{{ url('/wishlist') }}" class="hidden rounded-full p-2 text-royal-900 hover:bg-royal-50 sm:block" aria-label="Wishlist">
                <x-icon name="heart" class="size-[22px]" />
            </a>
            <a href="{{ url('/cart') }}" class="relative rounded-full p-2 text-royal-900 hover:bg-royal-50" aria-label="Shopping bag">
                <x-icon name="bag" class="size-[22px]" />
                @if (($cartCount ?? 0) > 0)
                    <span class="absolute right-0 top-0 flex size-4 items-center justify-center rounded-full bg-gold-gradient text-[10px] font-bold text-royal-950">{{ $cartCount }}</span>
                @endif
            </a>
        </div>
    </div>

    {{-- Desktop category navigation --}}
    <nav aria-label="Main" class="hidden border-t border-royal-100/70 lg:block">
        <ul class="container-x flex items-center justify-center gap-1 py-0.5">
            <li>
                <a href="{{ url('/shop?sort=newest') }}" class="group relative block px-4 py-3 text-sm font-medium tracking-wide text-royal-900 transition hover:text-royal-600">
                    New Arrivals
                    <span class="absolute inset-x-4 bottom-2 h-px origin-left scale-x-0 bg-gold-500 transition-transform duration-300 group-hover:scale-x-100"></span>
                </a>
            </li>

            @foreach ($navTop as $cat)
                @php $kids = $cat->children; @endphp
                @if ($kids->isEmpty())
                    <li>
                        <a href="{{ url('/category/'.$cat->slug) }}" class="group relative block px-4 py-3 text-sm font-medium tracking-wide text-royal-900 transition hover:text-royal-600">
                            {{ $cat->name }}
                            <span class="absolute inset-x-4 bottom-2 h-px origin-left scale-x-0 bg-gold-500 transition-transform duration-300 group-hover:scale-x-100"></span>
                        </a>
                    </li>
                @else
                    <li class="relative" x-data="{ open: false, t: null }"
                        @mouseenter="clearTimeout(t); open = true"
                        @mouseleave="t = setTimeout(() => open = false, 120)"
                        @focusout="if (! $el.contains($event.relatedTarget)) open = false"
                        @keydown.escape="open = false">
                        <div class="flex items-center">
                            <a href="{{ url('/category/'.$cat->slug) }}" class="py-3 pl-4 text-sm font-medium tracking-wide text-royal-900 transition hover:text-royal-600">{{ $cat->name }}</a>
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="{{ $cat->name }} sub-categories"
                                    class="py-3 pl-1 pr-3 text-royal-500 hover:text-royal-700">
                                <x-icon name="chevron-down" class="size-3.5 transition-transform duration-200" ::class="open && 'rotate-180'" />
                            </button>
                        </div>

                        <div x-cloak x-show="open"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute left-1/2 top-full z-50 w-64 -translate-x-1/2 pt-1">
                            <div class="overflow-hidden rounded-2xl border border-royal-100 bg-white p-2 shadow-2xl shadow-royal-950/15">
                                <div class="h-1 rounded-full bg-gold-gradient"></div>
                                <ul class="mt-2">
                                    @foreach ($kids as $kid)
                                        <li>
                                            <a href="{{ url('/category/'.$kid->slug) }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm text-royal-900 transition hover:bg-royal-50 hover:text-royal-700">
                                                {{ $kid->name }}
                                                <x-icon name="chevron-right" class="size-3.5 text-gold-600" />
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ url('/category/'.$cat->slug) }}" class="mt-1 block rounded-xl bg-royal-50 px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-royal-700 transition hover:bg-royal-100">
                                    Shop all {{ $cat->name }}
                                </a>
                            </div>
                        </div>
                    </li>
                @endif
            @endforeach

            @if ($navCategories->count() > $maxNav)
                <li>
                    <a href="{{ url('/shop') }}" class="block px-4 py-3 text-sm font-medium tracking-wide text-gold-700 transition hover:text-gold-600">All Jewellery</a>
                </li>
            @endif
        </ul>
    </nav>

    {{-- Mobile search panel --}}
    <div id="mobile-search" x-cloak x-show="search" x-collapse.duration.200ms
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="border-t border-royal-100 bg-white lg:hidden">
        <form action="{{ url('/search') }}" method="get" role="search" class="container-x py-3">
            <label for="mobile-search-input" class="sr-only">Search jewellery</label>
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-royal-400" />
                <input id="mobile-search-input" x-ref="mobileSearch" type="search" name="q" placeholder="Search earrings, necklaces…" autocomplete="off"
                       class="w-full rounded-full border border-royal-100 bg-ivory py-2.5 pl-11 pr-4 text-sm placeholder:text-royal-400 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-300/50">
            </div>
        </form>
    </div>
</header>

{{-- Mobile drawer: slides in from the left --}}
<div x-data x-effect="document.body.classList.toggle('overflow-hidden', $store.ui.mobileMenu)" class="lg:hidden">
    <div @click="$store.ui.mobileMenu = false" aria-hidden="true"
         class="fixed inset-0 z-50 bg-royal-950/60 backdrop-blur-[2px] transition-opacity duration-300 ease-in-out"
         :class="$store.ui.mobileMenu ? 'opacity-100' : 'pointer-events-none opacity-0'"></div>

    <aside id="mobile-menu" aria-label="Menu"
           class="fixed inset-y-0 left-0 z-50 flex w-[85vw] max-w-sm flex-col bg-ivory shadow-2xl transition-[translate,visibility] duration-300 ease-in-out"
           :class="$store.ui.mobileMenu ? 'visible translate-x-0' : 'invisible -translate-x-full'">

        <div class="flex items-center justify-between bg-royal-950 px-5 py-4">
            <a href="{{ url('/') }}"><x-logo :dark="true" /></a>
            <button type="button" @click="$store.ui.mobileMenu = false" aria-label="Close menu" class="-mr-2 rounded-full p-2 text-gold-200 hover:bg-white/10">
                <x-icon name="close" class="size-6" />
            </button>
        </div>
        <div class="h-1 bg-gold-gradient"></div>

        <nav aria-label="Mobile" class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ url('/shop?sort=newest') }}" class="flex items-center justify-between rounded-xl px-3 py-3 font-medium text-royal-900 hover:bg-royal-50">
                        New Arrivals <x-icon name="chevron-right" class="size-4 text-gold-600" />
                    </a>
                </li>
                @foreach ($navCategories as $cat)
                    @if ($cat->children->isEmpty())
                        <li>
                            <a href="{{ url('/category/'.$cat->slug) }}" class="flex items-center justify-between rounded-xl px-3 py-3 font-medium text-royal-900 hover:bg-royal-50">
                                {{ $cat->name }} <x-icon name="chevron-right" class="size-4 text-gold-600" />
                            </a>
                        </li>
                    @else
                        <li x-data="{ open: false }">
                            <button type="button" @click="open = !open" :aria-expanded="open"
                                    class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-left font-medium text-royal-900 hover:bg-royal-50">
                                {{ $cat->name }}
                                <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform duration-200" ::class="open && 'rotate-180'" />
                            </button>
                            <ul x-cloak x-show="open" x-transition.opacity.duration.200ms class="mb-1 ml-3 space-y-0.5 border-l-2 border-gold-300/60 pl-3">
                                <li><a href="{{ url('/category/'.$cat->slug) }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-royal-700 hover:bg-royal-50">Shop all {{ $cat->name }}</a></li>
                                @foreach ($cat->children as $kid)
                                    <li><a href="{{ url('/category/'.$kid->slug) }}" class="block rounded-lg px-3 py-2.5 text-sm text-royal-900 hover:bg-royal-50">{{ $kid->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="mt-4 space-y-0.5 border-t border-royal-100 pt-4">
                <a href="{{ url('/account') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-royal-900 hover:bg-royal-50"><x-icon name="user" class="size-5 text-royal-500" /> My account</a>
                <a href="{{ url('/wishlist') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-royal-900 hover:bg-royal-50"><x-icon name="heart" class="size-5 text-royal-500" /> Wishlist</a>
                <a href="{{ url('/cart') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-royal-900 hover:bg-royal-50"><x-icon name="bag" class="size-5 text-royal-500" /> Shopping bag</a>
            </div>
        </nav>
    </aside>
</div>
