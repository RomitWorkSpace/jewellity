@use('App\Support\Money')
@php
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-royal-950 placeholder:text-royal-300 focus:outline-none focus:ring-2 border-royal-200 focus:border-gold-400 focus:ring-gold-300/50';
    $any = collect($available)->sum() > 0;
@endphp
<x-layouts.storefront>
    <x-slot:seo><x-seo :title="$seo['title']" :noindex="true" /></x-slot:seo>

    <div class="container-x pb-20 pt-6 sm:pt-10">
        <a href="{{ $order->signedUrl() }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-royal-700 hover:text-royal-500"><x-icon name="chevron-left" class="size-4" /> Back to your order</a>

        <h1 class="font-serif text-3xl font-semibold text-royal-950 sm:text-4xl">Return items</h1>
        <p class="mt-2 max-w-2xl text-sm text-royal-800/80">Order <strong>{{ $order->number }}</strong>. You can ask for a return until <strong>{{ $deadline->format('j M Y') }}</strong> ({{ config('returns.window_days') }} days after delivery). We review every request, and only refund once the items reach us.</p>

        @if ($errors->has('return'))
            <p role="alert" class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('return') }}</p>
        @endif

        <form method="post" action="{{ $order->signedUrl('returns.store') }}" class="mt-8 grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-12" novalidate>
            @csrf
            <section aria-labelledby="pick-h">
                <h2 id="pick-h" class="font-serif text-xl font-semibold text-royal-950">Which items?</h2>
                <ul class="mt-3 divide-y divide-royal-100 border-y border-royal-100">
                    @foreach ($order->items as $item)
                        @php $max = $available[$item->id] ?? 0; @endphp
                        <li class="flex gap-4 py-4 {{ $max === 0 ? 'opacity-60' : '' }}">
                            <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-royal-100 to-gold-50 ring-1 ring-royal-900/5">
                                @if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="break-words font-medium text-royal-950">{{ $item->name }}</p>
                                @if ($item->options)<p class="mt-0.5 break-words text-xs text-royal-800/70">{{ $item->options }}</p>@endif
                                <p class="mt-1 text-sm text-royal-800/80">Paid {{ Money::format($item->netTotal()) }} for {{ $item->quantity }}</p>
                                @if ($max > 0)
                                    <label for="qty-{{ $item->id }}" class="mt-2 block text-sm font-medium text-royal-900">Quantity to return</label>
                                    <select id="qty-{{ $item->id }}" name="items[{{ $item->id }}]" class="{{ $field }} !mt-1 max-w-[12rem]">
                                        <option value="0">Not returning</option>
                                        @for ($n = 1; $n <= $max; $n++)<option value="{{ $n }}" @selected((int) old('items.'.$item->id) === $n)>{{ $n }}</option>@endfor
                                    </select>
                                @else
                                    <p class="mt-2 text-xs font-medium text-royal-800/70">
                                        {{ ! $item->returnable ? 'This item can’t be returned.' : ($item->refunded_quantity >= $item->quantity ? 'Already refunded.' : 'Not available to return.') }}
                                    </p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            <aside class="space-y-5 rounded-2xl border border-royal-100 bg-white p-5 sm:p-6">
                <div>
                    <label for="reason" class="block text-sm font-medium text-royal-900">Why are you returning? <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="reason" name="reason" required class="{{ $field }}" aria-invalid="{{ $errors->has('return') ? 'true' : 'false' }}">
                        <option value="">Choose a reason</option>
                        @foreach ($reasons as $key => $label)<option value="{{ $key }}" @selected(old('reason') === $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="comment" class="block text-sm font-medium text-royal-900">Anything we should know? <span class="text-xs font-normal text-royal-800/60">(optional)</span></label>
                    <textarea id="comment" name="comment" rows="4" maxlength="1000" class="{{ $field }}" placeholder="For example, what is wrong with the item">{{ old('comment') }}</textarea>
                </div>
                <ul class="space-y-1.5 text-xs text-royal-800/70">
                    <li>You get back what you paid for the returned items, to your original payment method.</li>
                    <li>Items should be unused and in their original packaging.</li>
                    <li>We'll email you the address to send them to once the return is approved.</li>
                </ul>
                @if ($any)
                    <button type="submit" class="btn-gold w-full !py-3">Request return</button>
                @endif
            </aside>
        </form>
    </div>
</x-layouts.storefront>
