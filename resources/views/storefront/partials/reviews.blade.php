{{-- Needs: $product, $reviews (paginator of approved), $distribution (rating => count), $eligibility --}}
@php
    $total = (int) $product->reviews_count;
    $mine = $eligibility->review;
    $editing = $mine && (old('rating') !== null || $errors->any());
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-royal-950 placeholder:text-royal-300 focus:outline-none focus:ring-2 border-royal-200 focus:border-gold-400 focus:ring-gold-300/50';
    $statusTone = ['pending' => 'bg-amber-50 text-amber-900 ring-amber-200', 'approved' => 'bg-green-50 text-green-900 ring-green-200', 'rejected' => 'bg-red-50 text-red-900 ring-red-200'];
@endphp
<section id="reviews" class="container-x scroll-mt-32 pt-14 sm:pt-20" aria-labelledby="reviews-heading">
    <x-section-heading id="reviews-heading" eyebrow="What shoppers say" title="Customer reviews" />

    @if (session('status'))
        <p role="status" class="mt-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-900 ring-1 ring-green-200">{{ session('status') }}</p>
    @endif

    <div class="mt-8 grid gap-10 lg:grid-cols-[18rem_minmax(0,1fr)] lg:gap-14">
        {{-- Summary + write --}}
        <div class="space-y-6">
            @if ($total > 0)
                <div class="flex items-center gap-4">
                    <p class="font-serif text-5xl font-semibold text-royal-950">{{ number_format($product->rating_avg, 1) }}</p>
                    <div>
                        <x-stars :rating="$product->rating_avg" size="size-5" />
                        <p class="mt-1 text-sm text-royal-800/70">{{ $total }} {{ \Illuminate\Support\Str::plural('review', $total) }}</p>
                    </div>
                </div>
                <ul class="space-y-1.5" aria-label="Rating breakdown">
                    @foreach ([5, 4, 3, 2, 1] as $star)
                        @php $n = (int) ($distribution[$star] ?? 0); $pct = $total ? round($n / $total * 100) : 0; @endphp
                        <li class="flex items-center gap-2 text-xs text-royal-800/80">
                            <span class="w-3 text-right">{{ $star }}</span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-royal-100"><span class="block h-full rounded-full bg-gold-gradient" style="width: {{ $pct }}%"></span></span>
                            <span class="w-8 text-right tabular-nums">{{ $n }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-royal-800/70">No reviews yet. Be the first to share your thoughts.</p>
            @endif

            {{-- Write / manage --}}
            @if ($eligibility->state === 'guest')
                <a href="{{ route('login') }}" class="btn-outline-dark inline-flex w-full items-center justify-center rounded-full border border-royal-900 px-6 py-3 text-sm font-semibold text-royal-900 transition hover:bg-royal-50">Sign in to write a review</a>
            @elseif ($eligibility->state === 'not_purchased')
                <p class="rounded-xl bg-royal-50 px-4 py-3 text-sm text-royal-800/80 ring-1 ring-royal-900/5">Reviews come from customers who have received this piece, so shoppers can trust them.</p>
            @endif
        </div>

        {{-- Form + list --}}
        <div class="min-w-0">
            @if ($eligibility->canWrite() || $mine)
                <div x-data="{ open: {{ ($eligibility->canWrite() && $errors->any()) || $editing ? 'true' : 'false' }} }" class="mb-8 rounded-2xl border border-royal-100 bg-white p-5 sm:p-6">
                    @if ($mine)
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-royal-950">Your review</p>
                                <span class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusTone[$mine->status->value] }}">{{ $mine->status->label() }}</span>
                            </div>
                            <div class="flex gap-3 text-sm font-semibold">
                                <button type="button" @click="open = !open" class="text-royal-700 hover:text-royal-500" x-text="open ? 'Cancel' : 'Edit'">Edit</button>
                                <form method="post" action="{{ route('reviews.destroy', $mine) }}" onsubmit="return confirm('Delete your review?')">@csrf @method('DELETE')<button class="text-red-600 hover:text-red-500">Delete</button></form>
                            </div>
                        </div>
                        @if ($mine->status->value === 'rejected' && $mine->moderation_note)
                            <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">Not published: {{ $mine->moderation_note }}. You can edit and resubmit it.</p>
                        @elseif ($mine->status->value === 'pending')
                            <p class="mt-3 text-sm text-royal-800/70">Thanks! It will appear here once our team has approved it.</p>
                        @endif
                        <div x-show="!open" class="mt-3">
                            <x-stars :rating="$mine->rating" />
                            @if ($mine->title)<p class="mt-1 font-medium text-royal-950">{{ $mine->title }}</p>@endif
                            <p class="mt-1 whitespace-pre-line break-words text-sm text-royal-800/90">{{ $mine->body }}</p>
                        </div>
                    @else
                        <button type="button" @click="open = !open" x-show="!open" class="btn-gold w-full sm:w-auto">Write a review</button>
                    @endif

                    <form x-cloak x-show="open" method="post" action="{{ $mine ? route('reviews.update', $mine) : route('reviews.store', $product->slug) }}" class="space-y-5 {{ $mine ? 'mt-5 border-t border-royal-100 pt-5' : '' }}" novalidate
                          x-data="{ rating: {{ (int) old('rating', $mine?->rating ?? 0) }}, hover: 0 }">
                        @csrf @if ($mine) @method('PUT') @endif
                        <fieldset>
                            <legend class="text-sm font-medium text-royal-900">Your rating <span class="text-red-600" aria-hidden="true">*</span></legend>
                            <div class="mt-2 flex gap-1" @mouseleave="hover = 0">
                                @foreach ([1, 2, 3, 4, 5] as $star)
                                    <label class="cursor-pointer rounded p-0.5 focus-within:outline focus-within:outline-2 focus-within:outline-gold-500" @mouseenter="hover = {{ $star }}">
                                        <input type="radio" name="rating" value="{{ $star }}" class="sr-only" x-model.number="rating" @checked((int) old('rating', $mine?->rating ?? 0) === $star)>
                                        <span class="sr-only">{{ $star }} {{ $star === 1 ? 'star' : 'stars' }}</span>
                                        <svg class="size-8 transition" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" :class="(hover || rating) >= {{ $star }} ? 'text-gold-500' : 'text-royal-200'"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    </label>
                                @endforeach
                            </div>
                            @error('rating')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </fieldset>
                        <div>
                            <label for="review-title" class="block text-sm font-medium text-royal-900">Title <span class="font-normal text-royal-800/60">(optional)</span></label>
                            <input id="review-title" name="title" maxlength="100" value="{{ old('title', $mine?->title) }}" placeholder="Sum it up in a few words" class="{{ $field }}">
                            @error('title')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="review-body" class="block text-sm font-medium text-royal-900">Your review <span class="text-red-600" aria-hidden="true">*</span></label>
                            <textarea id="review-body" name="body" rows="4" maxlength="{{ config('reviews.body_max') }}" placeholder="What did you like? How is the quality, finish and fit?" aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}" class="{{ $field }}">{{ old('body', $mine?->body) }}</textarea>
                            @error('body')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <p class="text-xs text-royal-800/60">Your first name and the initial of your last name are shown with the review. It is published after a quick check by our team.</p>
                        <button type="submit" class="btn-gold !py-3">{{ $mine ? 'Save changes' : 'Submit review' }}</button>
                    </form>
                </div>
            @endif

            @if ($reviews->isEmpty())
                <p class="text-sm text-royal-800/70">{{ $total > 0 ? 'No more reviews.' : 'Nothing here yet.' }}</p>
            @else
                <ul class="divide-y divide-royal-100">
                    @foreach ($reviews as $review)
                        <li class="py-6 first:pt-0">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <x-stars :rating="$review->rating" />
                                @if ($review->title)<p class="font-semibold text-royal-950">{{ $review->title }}</p>@endif
                            </div>
                            <p class="mt-2 whitespace-pre-line break-words text-[15px] leading-relaxed text-royal-800/90">{{ $review->body }}</p>
                            <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-royal-800/60">
                                <span class="font-medium text-royal-900">{{ $review->displayName() }}</span>
                                @if ($review->verified_purchase)<span class="inline-flex items-center gap-1 text-green-800"><x-icon name="check" class="size-3.5" /> Verified purchase</span>@endif
                                <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j M Y') }}</time>
                            </p>
                        </li>
                    @endforeach
                </ul>
                {{ $reviews->links('pagination.storefront') }}
            @endif
        </div>
    </div>
</section>
