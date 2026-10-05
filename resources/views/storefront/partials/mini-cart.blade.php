{{-- Slide-in mini bag, opened from the header bag icon and after "Add to bag". --}}
<div x-data="miniBag()" x-effect="$store.ui.miniCart ? open() : release()" @keydown.escape.window="$store.ui.miniCart = false">
    <div x-cloak @click="$store.ui.miniCart = false" aria-hidden="true"
         class="fixed inset-0 z-[60] bg-royal-950/60 backdrop-blur-[2px] transition-opacity duration-300 ease-in-out"
         :class="$store.ui.miniCart ? 'opacity-100' : 'pointer-events-none opacity-0'"></div>

    <aside x-cloak id="mini-cart" role="dialog" aria-modal="true" aria-label="Your bag"
           class="fixed inset-y-0 right-0 z-[60] flex w-full max-w-md flex-col bg-ivory shadow-2xl transition-[translate,visibility] duration-300 ease-in-out"
           :class="$store.ui.miniCart ? 'visible translate-x-0' : 'invisible translate-x-full'">
        <div class="flex items-center justify-between bg-royal-950 px-5 py-4">
            <h2 class="flex items-center gap-2 font-serif text-xl font-semibold text-gold-100">
                Your bag <span x-cloak x-show="$store.cart.count > 0" class="rounded-full bg-gold-gradient px-2 py-0.5 font-sans text-xs font-bold text-royal-950" x-text="$store.cart.count"></span>
            </h2>
            <button type="button" @click="$store.ui.miniCart = false" aria-label="Close bag" x-ref="close" class="-mr-2 rounded-full p-2 text-gold-200 hover:bg-white/10">
                <x-icon name="close" class="size-6" />
            </button>
        </div>
        <div class="h-1 bg-gold-gradient"></div>

        <div x-ref="root" class="min-h-0 flex-1 transition-opacity" :class="busy && 'pointer-events-none opacity-60'" :aria-busy="busy">
            <div class="flex h-full items-center justify-center" x-show="!loaded"><div class="size-7 animate-spin rounded-full border-2 border-royal-200 border-t-gold-500"></div></div>
        </div>
    </aside>
</div>
