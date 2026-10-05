@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        // 1 … (current-1) current (current+1) … last
        $pages = collect([1, $last, $current - 1, $current, $current + 1])->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
        $item = 'flex size-10 items-center justify-center rounded-full text-sm font-medium transition';
    @endphp
    <nav role="navigation" aria-label="Pagination" class="mt-12 flex flex-col items-center gap-4">
        <ul class="flex flex-wrap items-center justify-center gap-1.5">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $item }} cursor-not-allowed text-royal-300" aria-disabled="true" aria-label="Previous page"><x-icon name="chevron-right" class="size-4 rotate-180" /></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $item }} text-royal-800 hover:bg-royal-50" aria-label="Previous page"><x-icon name="chevron-right" class="size-4 rotate-180" /></a>
                @endif
            </li>

            @foreach ($pages as $i => $page)
                @if ($i > 0 && $page - $pages[$i - 1] > 1)
                    <li><span class="flex size-10 items-end justify-center pb-2 text-royal-400" aria-hidden="true">…</span></li>
                @endif
                <li>
                    @if ($page === $current)
                        <span aria-current="page" class="{{ $item }} bg-gold-gradient font-semibold text-royal-950 shadow shadow-gold-500/30">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="{{ $item }} text-royal-800 hover:bg-royal-50" aria-label="Page {{ $page }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $item }} text-royal-800 hover:bg-royal-50" aria-label="Next page"><x-icon name="chevron-right" class="size-4" /></a>
                @else
                    <span class="{{ $item }} cursor-not-allowed text-royal-300" aria-disabled="true" aria-label="Next page"><x-icon name="chevron-right" class="size-4" /></span>
                @endif
            </li>
        </ul>
        <p class="text-xs text-royal-800/70">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
    </nav>
@endif
