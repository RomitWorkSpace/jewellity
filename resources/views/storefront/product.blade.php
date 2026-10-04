@php
    $pill = 'inline-flex min-w-12 items-center justify-center rounded-full border px-4 py-2.5 text-sm font-medium transition focus-visible:outline-offset-2';
    $accordion = 'group border-b border-royal-100 py-1';
    $summary = 'flex cursor-pointer list-none items-center justify-between py-4 text-sm font-semibold uppercase tracking-wider text-royal-950';
    $descriptionParagraphs = preg_split('/\R{2,}/', trim((string) $product->description)) ?: [];
    $hasCrumbCategory = count($breadcrumbs) > 2;
@endphp
<x-layouts.storefront>
    <x-slot:seo>
        <x-seo :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']"
               :image="$seo['image']" type="product" :json-ld="$seo['jsonLd']" />
    </x-slot:seo>

    <div x-data="productPage(@js($pageData))"
         @keydown.window.escape="lightbox && closeLightbox()"
         @keydown.window.arrow-left="lightbox && prev()"
         @keydown.window.arrow-right="lightbox && next()">

        <div class="container-x pt-5 sm:pt-8">
            <x-breadcrumbs :items="$breadcrumbs" />
        </div>

        {{-- ================================ MAIN ================================ --}}
        <div class="container-x grid gap-8 py-6 sm:py-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-14 xl:gap-20">

            {{-- ------------------------------ GALLERY ------------------------------ --}}
            <section aria-label="Product images" class="min-w-0 lg:sticky lg:top-40 lg:self-start">
                @if ($images)
                    <div class="flex gap-4">
                        @if (count($images) > 1)
                            <ul class="hidden max-h-[34rem] w-[4.5rem] shrink-0 flex-col gap-3 overflow-y-auto lg:flex" aria-label="Thumbnails">
                                @foreach ($images as $i => $img)
                                    <li>
                                        <button type="button" @click="goTo({{ $i }})" aria-label="Show image {{ $i + 1 }}"
                                                :aria-current="active === {{ $i }}"
                                                class="block aspect-[4/5] w-full overflow-hidden rounded-xl ring-2 ring-offset-2 ring-offset-ivory transition"
                                                :class="active === {{ $i }} ? 'ring-gold-500' : 'ring-transparent opacity-70 hover:opacity-100'">
                                            <img src="{{ $img['url'] }}" alt="" loading="lazy" class="size-full object-cover">
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="group/gallery relative min-w-0 flex-1">
                            <div x-ref="track" @scroll.passive="onScroll"
                                 class="flex snap-x snap-mandatory overflow-x-auto rounded-2xl bg-royal-50 ring-1 ring-royal-900/5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                @foreach ($images as $i => $img)
                                    <div class="relative aspect-[4/5] w-full shrink-0 cursor-zoom-in snap-center snap-always overflow-hidden"
                                         @mousemove="zoomMove($event)" @mouseleave="zoomReset($event)" @click="openLightbox({{ $i }})">
                                        <img src="{{ $img['url'] }}" alt="{{ $img['alt'] }}" @if ($i > 0) loading="lazy" @endif
                                             draggable="false" class="size-full select-none object-cover transition-transform duration-200 ease-out">
                                    </div>
                                @endforeach
                            </div>

                            {{-- badges --}}
                            <div class="pointer-events-none absolute left-3 top-3 flex flex-col items-start gap-1.5">
                                <span x-cloak x-show="!variant.inStock" class="rounded-full bg-royal-950/85 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">Sold out</span>
                                <span x-cloak x-show="variant.inStock && variant.discount" x-text="variant.discount + '% off'" class="rounded-full bg-gold-gradient px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-royal-950"></span>
                            </div>

                            <button type="button" @click="openLightbox()" aria-label="Open full-size image"
                                    class="absolute right-3 top-3 hidden rounded-full bg-white/90 p-2.5 text-royal-900 shadow transition hover:bg-white sm:block">
                                <x-icon name="expand" class="size-4" />
                            </button>

                            @if (count($images) > 1)
                                <button type="button" @click="prev()" aria-label="Previous image"
                                        class="absolute left-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/90 p-2.5 text-royal-900 opacity-0 shadow transition hover:bg-white group-hover/gallery:opacity-100 focus-visible:opacity-100 md:block">
                                    <x-icon name="chevron-left" class="size-5" />
                                </button>
                                <button type="button" @click="next()" aria-label="Next image"
                                        class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/90 p-2.5 text-royal-900 opacity-0 shadow transition hover:bg-white group-hover/gallery:opacity-100 focus-visible:opacity-100 md:block">
                                    <x-icon name="chevron-right" class="size-5" />
                                </button>
                                <p class="pointer-events-none absolute bottom-3 right-3 rounded-full bg-royal-950/70 px-2.5 py-1 text-xs font-medium text-white lg:hidden" aria-hidden="true">
                                    <span x-text="active + 1">1</span> / {{ count($images) }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if (count($images) > 1)
                        <div class="mt-4 flex justify-center gap-1.5 lg:hidden" aria-hidden="true">
                            @foreach ($images as $i => $img)
                                <button type="button" tabindex="-1" @click="goTo({{ $i }})" class="h-1.5 rounded-full transition-all duration-300"
                                        :class="active === {{ $i }} ? 'w-6 bg-gold-500' : 'w-1.5 bg-royal-200'"></button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex aspect-[4/5] items-center justify-center rounded-2xl bg-gradient-to-br from-royal-100 via-royal-50 to-gold-50 text-royal-300 ring-1 ring-royal-900/5">
                        <x-icon name="sparkle" class="size-16" />
                    </div>
                @endif
            </section>

            {{-- ------------------------------ DETAILS ------------------------------ --}}
            <section aria-label="Product details" class="min-w-0">
                @if ($hasCrumbCategory)
                    <a href="{{ $category['url'] }}" class="text-xs font-semibold uppercase tracking-[0.22em] text-gold-700 hover:text-gold-600">{{ $category['name'] }}</a>
                @endif
                <h1 class="mt-2 font-serif text-3xl font-semibold leading-tight text-royal-950 sm:text-4xl">{{ $product->name }}</h1>

                {{-- Price --}}
                <div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-3xl font-semibold text-royal-800" x-text="variant.price">{{ \App\Support\Money::format($initial->price) }}</span>
                    <span x-cloak x-show="variant.compare" x-text="variant.compare" class="text-lg text-royal-800/50 line-through"></span>
                    <span x-cloak x-show="variant.discount" x-text="'Save ' + variant.discount + '%'" class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800"></span>
                </div>

                @if ($product->short_description)
                    <p class="mt-4 text-[15px] leading-relaxed text-royal-800/80">{{ $product->short_description }}</p>
                @endif

                {{-- Options --}}
                @foreach ($optionGroups as $group)
                    <fieldset class="mt-7">
                        <legend class="text-sm text-royal-800/70">
                            {{ $group['name'] }}: <span class="font-semibold text-royal-950" x-text="selectedLabel({ slug: '{{ $group['slug'] }}' })"></span>
                        </legend>
                        <div role="radiogroup" aria-label="{{ $group['name'] }}" class="mt-3 flex flex-wrap gap-2.5">
                            @foreach ($group['values'] as $value)
                                <button type="button" role="radio" @click="choose('{{ $group['slug'] }}', '{{ $value['slug'] }}')"
                                        :aria-checked="isSelected('{{ $group['slug'] }}', '{{ $value['slug'] }}')"
                                        class="{{ $pill }}"
                                        :class="[
                                            isSelected('{{ $group['slug'] }}', '{{ $value['slug'] }}')
                                                ? 'border-royal-900 bg-royal-900 text-white shadow-md'
                                                : 'border-royal-200 bg-white text-royal-900 hover:border-gold-500',
                                            !isAvailable('{{ $group['slug'] }}', '{{ $value['slug'] }}') && 'border-dashed opacity-50',
                                            isSoldOut('{{ $group['slug'] }}', '{{ $value['slug'] }}') && 'line-through opacity-60',
                                        ]">{{ $value['label'] }}</button>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                {{-- Stock --}}
                <p class="mt-6 flex items-center gap-2 text-sm font-medium" role="status">
                    <span class="size-2 rounded-full" :class="{ 'bg-green-600': stockText.tone === 'in', 'bg-amber-500': stockText.tone === 'low', 'bg-red-600': stockText.tone === 'out' }"></span>
                    <span :class="{ 'text-green-800': stockText.tone === 'in', 'text-amber-800': stockText.tone === 'low', 'text-red-700': stockText.tone === 'out' }" x-text="stockText.text">In stock</span>
                </p>

                {{-- Quantity + CTA --}}
                <div class="mt-5 flex flex-wrap items-stretch gap-3">
                    <div class="inline-flex items-center rounded-full border border-royal-200 bg-white" role="group" aria-label="Quantity">
                        <button type="button" @click="dec()" :disabled="qty <= 1 || !variant.inStock" aria-label="Decrease quantity" class="rounded-full p-3 text-royal-900 transition hover:bg-royal-50 disabled:opacity-30">
                            <x-icon name="minus" class="size-4" />
                        </button>
                        <span class="w-8 text-center text-sm font-semibold tabular-nums" aria-live="polite" x-text="qty">1</span>
                        <button type="button" @click="inc()" :disabled="qty >= limit || !variant.inStock" aria-label="Increase quantity" class="rounded-full p-3 text-royal-900 transition hover:bg-royal-50 disabled:opacity-30">
                            <x-icon name="plus" class="size-4" />
                        </button>
                    </div>

                    <button type="button" x-ref="cta" @click="addToBag()" :disabled="!canBuy"
                            class="btn-gold min-w-[9.5rem] flex-1 whitespace-nowrap !px-4 !py-3.5 sm:!px-7 disabled:cursor-not-allowed disabled:opacity-50 disabled:grayscale disabled:hover:brightness-100">
                        <x-icon name="bag" class="size-5" />
                        <span x-text="!variant.inStock ? 'Out of stock' : (adding ? 'Adding…' : 'Add to bag')">Add to bag</span>
                    </button>

                    <button type="button" @click="share()" aria-label="Share this product"
                            class="rounded-full border border-royal-200 bg-white p-3 text-royal-900 transition hover:border-gold-500 hover:text-royal-600">
                        <x-icon name="share" class="size-5" />
                    </button>
                </div>
                <p x-cloak x-show="error" x-text="error" role="alert" class="mt-3 rounded-lg bg-red-50 px-4 py-2.5 text-sm text-red-700"></p>

                {{-- Reassurance --}}
                <ul class="mt-6 grid gap-2.5 rounded-2xl bg-royal-50/70 p-4 text-sm text-royal-900 ring-1 ring-royal-900/5">
                    @foreach (config('storefront.pdp.perks') as $perk)
                        <li class="flex items-center gap-3"><x-icon :name="$perk['icon']" class="size-5 shrink-0 text-gold-700" /> {{ $perk['text'] }}</li>
                    @endforeach
                </ul>

                {{-- Accordions --}}
                <div class="mt-8 border-t border-royal-100">
                    @if ($descriptionParagraphs && $descriptionParagraphs[0] !== '')
                        <details open class="{{ $accordion }}">
                            <summary class="{{ $summary }}">Description <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" /></summary>
                            <div class="space-y-3 pb-4 text-[15px] leading-relaxed text-royal-800/90">
                                @foreach ($descriptionParagraphs as $para)
                                    <p>{!! nl2br(e($para)) !!}</p>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    <details @if (! ($descriptionParagraphs && $descriptionParagraphs[0] !== '')) open @endif class="{{ $accordion }}">
                        <summary class="{{ $summary }}">Product details <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" /></summary>
                        <dl class="grid grid-cols-[8rem_1fr] gap-x-4 gap-y-2.5 pb-4 text-sm">
                            <dt class="text-royal-800/60">SKU</dt><dd class="font-medium text-royal-950" x-text="variant.sku">{{ $initial->sku }}</dd>
                            <template x-for="(value, name) in variant.details" :key="name">
                                <div class="contents"><dt class="text-royal-800/60" x-text="name"></dt><dd class="font-medium text-royal-950" x-text="value"></dd></div>
                            </template>
                            <template x-if="variant.weight">
                                <div class="contents"><dt class="text-royal-800/60">Weight</dt><dd class="font-medium text-royal-950" x-text="variant.weight + ' g'"></dd></div>
                            </template>
                            @if ($hasCrumbCategory)
                                <dt class="text-royal-800/60">Category</dt><dd class="font-medium text-royal-950"><a href="{{ $category['url'] }}" class="underline decoration-gold-500 underline-offset-2 hover:text-royal-600">{{ $category['name'] }}</a></dd>
                            @endif
                        </dl>
                    </details>

                    <details class="{{ $accordion }}">
                        <summary class="{{ $summary }}">Shipping &amp; returns <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" /></summary>
                        <div class="space-y-3 pb-4 text-[15px] leading-relaxed text-royal-800/90">
                            <p>{{ config('storefront.pdp.shipping') }}</p>
                            <p>{{ config('storefront.pdp.returns') }}</p>
                        </div>
                    </details>

                    <details class="{{ $accordion }}">
                        <summary class="{{ $summary }}">Jewellery care <x-icon name="chevron-down" class="size-4 text-gold-600 transition-transform group-open:rotate-180" /></summary>
                        <ul class="list-disc space-y-2 pb-4 pl-5 text-[15px] leading-relaxed text-royal-800/90 marker:text-gold-500">
                            @foreach (config('storefront.pdp.care') as $tip)
                                <li>{{ $tip }}</li>
                            @endforeach
                        </ul>
                    </details>
                </div>
            </section>
        </div>

        {{-- ============================ RELATED ============================ --}}
        @if ($related->isNotEmpty())
            <section class="container-x pt-10 sm:pt-16" aria-labelledby="related-heading">
                <x-section-heading id="related-heading" eyebrow="Complete the look" title="You may also like" />
                <div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ========================== RECENTLY VIEWED ========================== --}}
        <section x-data="recentlyViewed(@js($pageData['recent']))" x-cloak x-show="items.length" class="container-x pt-14 sm:pt-20" aria-labelledby="recent-heading">
            <x-section-heading id="recent-heading" eyebrow="Pick up where you left off" title="Recently viewed" />
            <ul class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 lg:grid-cols-4">
                <template x-for="item in items" :key="item.slug">
                    <li>
                        <a :href="href(item)" class="group block">
                            <div class="aspect-[4/5] overflow-hidden rounded-2xl bg-gradient-to-br from-royal-100 to-gold-50 ring-1 ring-royal-900/5">
                                <img x-show="item.image" :src="item.image" alt="" loading="lazy" class="size-full object-cover transition duration-700 group-hover:scale-105">
                            </div>
                            <p class="mt-3 line-clamp-2 px-1 text-sm font-medium text-royal-950 group-hover:text-royal-600 sm:text-base" x-text="item.name"></p>
                            <p class="mt-1 px-1 text-sm font-semibold text-royal-800" x-text="item.price"></p>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        {{-- ====================== STICKY MOBILE BUY BAR ====================== --}}
        <div x-cloak class="fixed inset-x-0 bottom-0 z-40 border-t border-royal-100 bg-ivory/95 px-4 py-3 shadow-[0_-8px_24px_-12px_rgba(31,11,54,0.3)] backdrop-blur transition-transform duration-300 lg:hidden"
             :class="showBar ? 'translate-y-0' : 'translate-y-full'" :aria-hidden="!showBar" :inert="!showBar">
            <div class="mx-auto flex max-w-xl items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-royal-950">{{ $product->name }}</p>
                    <p class="text-sm font-semibold text-royal-800"><span x-text="variant.price"></span>
                        <span x-show="variant.compare" x-text="variant.compare" class="ml-1 font-normal text-royal-800/50 line-through"></span>
                    </p>
                </div>
                <button type="button" @click="addToBag()" :disabled="!canBuy" class="btn-gold shrink-0 !px-6 !py-3 disabled:cursor-not-allowed disabled:opacity-50 disabled:grayscale">
                    <span x-text="!variant.inStock ? 'Out of stock' : (adding ? 'Adding…' : 'Add to bag')">Add to bag</span>
                </button>
            </div>
        </div>

        {{-- ============================= LIGHTBOX ============================= --}}
        @if ($images)
            <div x-cloak x-show="lightbox" x-transition.opacity.duration.200ms x-trap.noscroll="lightbox"
                 role="dialog" aria-modal="true" aria-label="Product image viewer" class="fixed inset-0 z-[80] flex flex-col bg-royal-950/95">
                <div class="flex items-center justify-between px-4 py-3 text-white">
                    <p class="text-sm" aria-live="polite"><span x-text="active + 1"></span> / {{ count($images) }}</p>
                    <button type="button" @click="closeLightbox()" aria-label="Close viewer" class="rounded-full p-2.5 hover:bg-white/10"><x-icon name="close" class="size-6" /></button>
                </div>
                <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-6" @click.self="closeLightbox()">
                    <template x-if="images[active]">
                        <img :src="images[active].url" :alt="images[active].alt" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
                    </template>
                    @if (count($images) > 1)
                        <button type="button" @click="prev()" aria-label="Previous image" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/15 p-3 text-white backdrop-blur hover:bg-white/25"><x-icon name="chevron-left" class="size-6" /></button>
                        <button type="button" @click="next()" aria-label="Next image" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/15 p-3 text-white backdrop-blur hover:bg-white/25"><x-icon name="chevron-right" class="size-6" /></button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.storefront>
