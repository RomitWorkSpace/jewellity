@use('App\Support\Money')
@php
    $config = [
        // Contact phone: the account's, else the default address's, so a signed-in customer rarely has to type it.
        'user' => $user ? ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone ?: $defaultAddress?->phone] : null,
        'addresses' => $addresses->map(fn ($a) => ['id' => $a->id, 'label' => $a->label ?: 'Address', 'name' => $a->name, 'phone' => $a->phone, 'text' => $a->formatted(), 'default' => $a->is_default])->values(),
        'defaultId' => $defaultAddress?->id,
        'states' => \App\Support\IndianStates::all(),
        'endpoints' => ['store' => route('checkout.store'), 'verify' => route('checkout.verify')],
    ];
    $input = 'mt-1.5 block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-royal-950 placeholder:text-royal-300 focus:outline-none focus:ring-2';
    $ok = 'border-royal-200 focus:border-gold-400 focus:ring-gold-300/50';
    $bad = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $label = 'block text-sm font-medium text-royal-900';
@endphp
<x-layouts.storefront>
    <x-slot:seo><x-seo title="Checkout – {{ config('app.name') }}" :noindex="true" /></x-slot:seo>

    <div x-data="checkoutPage(@js($config))" class="container-x pb-32 pt-6 sm:pt-10 lg:pb-16">
        <x-breadcrumbs :items="[['name' => 'Home', 'url' => url('/')], ['name' => 'Shopping bag', 'url' => url('/cart')], ['name' => 'Checkout', 'url' => url('/checkout')]]" />
        <h1 class="mt-4 font-serif text-3xl font-semibold text-royal-950 sm:text-4xl">Checkout</h1>

        <noscript><p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">Checkout needs JavaScript to open the secure payment window. Please enable it and reload.</p></noscript>

        @unless ($paymentsReady)
            <p role="alert" class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">Online payments are not switched on yet (the Razorpay keys are missing). You can fill the form, but paying is disabled.</p>
        @endunless

        {{-- Mobile: collapsible summary --}}
        <details class="mt-6 rounded-2xl border border-royal-100 bg-white lg:hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3.5 text-sm font-semibold text-royal-950">
                <span class="flex items-center gap-2"><x-icon name="bag" class="size-5 text-royal-600" /> Order summary ({{ $summary->count() }})</span>
                <span class="text-royal-900">{{ Money::format($totals->total()) }}</span>
            </summary>
            <div class="border-t border-royal-100 px-4 pb-4">@include('storefront.checkout._summary-lines')</div>
        </details>

        <form @submit.prevent="submit()" novalidate class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_25rem] lg:items-start lg:gap-12">
            <div class="min-w-0 space-y-6">
                <p x-cloak x-show="message" x-text="message" role="alert" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200"></p>
                <div x-cloak x-show="retry" class="-mt-3 flex flex-wrap gap-3">
                    <button type="button" @click="retryPayment()" :disabled="busy" class="btn-gold !py-2.5">Try payment again</button>
                    <a :href="retry?.redirect" class="inline-flex items-center rounded-full border border-royal-300 px-6 py-2.5 text-sm font-semibold text-royal-900 hover:bg-royal-50">View my order</a>
                </div>

                {{-- Contact --}}
                <section class="rounded-2xl border border-royal-100 bg-white p-5 sm:p-6" aria-labelledby="contact-h">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="contact-h" class="font-serif text-xl font-semibold text-royal-950">Contact</h2>
                        @guest<a href="{{ route('login') }}" class="text-sm font-semibold text-royal-700 hover:text-royal-500">Have an account? Sign in</a>@endguest
                    </div>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="email" class="{{ $label }}">Email <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="email" type="email" x-model="contact.email" autocomplete="email" required :aria-invalid="!!err('email')" class="{{ $input }}" :class="err('email') ? '{{ $bad }}' : '{{ $ok }}'" placeholder="you@example.com">
                            <p x-show="err('email')" x-text="err('email')" class="mt-1.5 text-xs text-red-600"></p>
                            <p x-show="!err('email')" class="mt-1.5 text-xs text-royal-800/60">We email your order confirmation here.</p>
                        </div>
                        <div>
                            <label for="phone" class="{{ $label }}">Mobile number <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="phone" type="tel" inputmode="numeric" x-model="contact.phone" autocomplete="tel" required :aria-invalid="!!err('phone')" class="{{ $input }}" :class="err('phone') ? '{{ $bad }}' : '{{ $ok }}'" placeholder="98765 43210">
                            <p x-show="err('phone')" x-text="err('phone')" class="mt-1.5 text-xs text-red-600"></p>
                            <p x-show="!err('phone')" class="mt-1.5 text-xs text-royal-800/60">For delivery updates.</p>
                        </div>
                    </div>
                </section>

                {{-- Delivery address --}}
                <section class="rounded-2xl border border-royal-100 bg-white p-5 sm:p-6" aria-labelledby="addr-h">
                    <h2 id="addr-h" class="font-serif text-xl font-semibold text-royal-950">Delivery address</h2>

                    @if ($addresses->isNotEmpty())
                        <fieldset class="mt-4 grid gap-3">
                            <legend class="sr-only">Choose a saved address</legend>
                            <template x-for="a in {{ \Illuminate\Support\Js::from($config['addresses']) }}" :key="a.id">
                                <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition" :class="mode === 'saved' && addressId === a.id ? 'border-gold-400 bg-gold-50/50 ring-1 ring-gold-300/60' : 'border-royal-200 hover:border-gold-300'">
                                    <input type="radio" name="saved_address" class="mt-1 size-4 accent-royal-700" :value="a.id" :checked="mode === 'saved' && addressId === a.id" @change="mode = 'saved'; addressId = a.id">
                                    <span class="min-w-0 text-sm">
                                        <span class="font-semibold text-royal-950" x-text="a.label + (a.default ? ' · Default' : '')"></span>
                                        <span class="mt-1 block text-royal-900" x-text="a.name + ' · ' + a.phone"></span>
                                        <span class="mt-0.5 block whitespace-pre-line text-royal-800/80" x-text="a.text"></span>
                                    </span>
                                </label>
                            </template>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 text-sm font-semibold text-royal-900 transition" :class="mode === 'new' ? 'border-gold-400 bg-gold-50/50 ring-1 ring-gold-300/60' : 'border-royal-200 hover:border-gold-300'">
                                <input type="radio" name="saved_address" class="size-4 accent-royal-700" :checked="mode === 'new'" @change="mode = 'new'"> Use a different address
                            </label>
                        </fieldset>
                    @endif

                    <div x-show="mode === 'new'" class="mt-5 grid gap-5 sm:grid-cols-2" @if ($addresses->isNotEmpty()) x-cloak @endif>
                        @foreach ([
                            ['name', 'Full name', 'text', 'name', 'sm:col-span-1', ''],
                            ['address_phone', 'Mobile number', 'tel', 'tel', 'sm:col-span-1', '98765 43210'],
                            ['line1', 'Address line 1', 'text', 'address-line1', 'sm:col-span-2', 'House / flat no., building, street'],
                            ['line2', 'Address line 2', 'text', 'address-line2', 'sm:col-span-2', 'Area, locality (optional)'],
                            ['landmark', 'Landmark', 'text', 'off', 'sm:col-span-2', 'Near… (optional)'],
                            ['pincode', 'Pincode', 'text', 'postal-code', 'sm:col-span-1', '400001'],
                            ['city', 'City / town', 'text', 'address-level2', 'sm:col-span-1', ''],
                        ] as [$field, $text, $type, $auto, $span, $ph])
                            <div class="{{ $span }}">
                                <label for="f_{{ $field }}" class="{{ $label }}">{{ $text }}@if (! in_array($field, ['line2', 'landmark']))<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
                                <input id="f_{{ $field }}" type="{{ $type }}" x-model="address.{{ $field }}" autocomplete="{{ $auto === 'off' ? 'off' : 'shipping '.$auto }}" placeholder="{{ $ph }}"
                                       @if ($field === 'pincode') inputmode="numeric" maxlength="6" @endif @if ($field === 'address_phone') inputmode="numeric" @endif
                                       :aria-invalid="!!err('{{ $field }}')" class="{{ $input }}" :class="err('{{ $field }}') ? '{{ $bad }}' : '{{ $ok }}'">
                                <p x-show="err('{{ $field }}')" x-text="err('{{ $field }}')" class="mt-1.5 text-xs text-red-600"></p>
                            </div>
                        @endforeach
                        <div class="sm:col-span-2">
                            <label for="f_state" class="{{ $label }}">State <span class="text-red-600" aria-hidden="true">*</span></label>
                            <select id="f_state" x-model="address.state" autocomplete="shipping address-level1" :aria-invalid="!!err('state')" class="{{ $input }}" :class="err('state') ? '{{ $bad }}' : '{{ $ok }}'">
                                <option value="">Select state…</option>
                                <template x-for="s in states" :key="s"><option :value="s" x-text="s"></option></template>
                            </select>
                            <p x-show="err('state')" x-text="err('state')" class="mt-1.5 text-xs text-red-600"></p>
                        </div>
                        @auth
                            <label class="flex items-center gap-2 text-sm text-royal-900 sm:col-span-2"><input type="checkbox" x-model="saveAddress" class="size-4 rounded border-royal-300 accent-royal-700"> Save this address for next time</label>
                        @endauth
                    </div>
                    <p x-show="err('address_id')" x-text="err('address_id')" class="mt-3 text-xs text-red-600"></p>
                </section>

                <section class="rounded-2xl border border-royal-100 bg-white p-5 sm:p-6" aria-labelledby="note-h">
                    <h2 id="note-h" class="font-serif text-xl font-semibold text-royal-950">Order note <span class="font-sans text-sm font-normal text-royal-800/60">(optional)</span></h2>
                    <label for="note" class="sr-only">Order note</label>
                    <textarea id="note" x-model="note" rows="2" maxlength="500" placeholder="Gift message or delivery instructions" class="{{ $input }} {{ $ok }}"></textarea>
                </section>
            </div>

            {{-- Desktop summary --}}
            <aside aria-label="Order summary" class="hidden rounded-2xl border border-royal-100 bg-white p-6 shadow-sm lg:sticky lg:top-40 lg:block">
                <h2 class="font-serif text-xl font-semibold text-royal-950">Order summary</h2>
                @include('storefront.checkout._summary-lines')
                <button type="submit" :disabled="busy || paying || {{ $paymentsReady ? 'false' : 'true' }}" class="btn-gold mt-6 w-full !py-3.5 disabled:cursor-not-allowed disabled:opacity-60">
                    <x-icon name="shield" class="size-5" /> <span x-text="busy ? 'Please wait…' : (paying ? 'Waiting for payment…' : 'Pay {{ Money::format($totals->total()) }} securely')">Pay {{ Money::format($totals->total()) }} securely</span>
                </button>
                <p class="mt-3 text-center text-xs text-royal-800/60">Secure payment by Razorpay · UPI, cards, netbanking, wallets</p>
            </aside>

            {{-- Mobile pay bar --}}
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-royal-100 bg-ivory/95 px-4 py-3 shadow-[0_-8px_24px_-12px_rgba(31,11,54,0.3)] backdrop-blur lg:hidden">
                <div class="mx-auto flex max-w-xl items-center gap-4">
                    <div class="min-w-0"><p class="text-xs text-royal-800/70">Total</p><p class="text-lg font-semibold leading-tight text-royal-900">{{ Money::format($totals->total()) }}</p></div>
                    <button type="submit" :disabled="busy || paying || {{ $paymentsReady ? 'false' : 'true' }}" class="btn-gold ml-auto !px-6 !py-3 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="busy ? 'Please wait…' : (paying ? 'Paying…' : 'Pay securely')">Pay securely</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.storefront>
