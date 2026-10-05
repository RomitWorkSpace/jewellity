@if ($summary->notices)
    <div role="status" class="rounded-xl border border-amber-300/70 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="flex items-center gap-2 font-semibold"><x-icon name="sparkle" class="size-4 text-amber-600" /> Your bag was updated</p>
        <ul class="mt-1.5 list-disc space-y-1 pl-5 marker:text-amber-500">
            @foreach ($summary->notices as $notice)<li>{{ $notice }}</li>@endforeach
        </ul>
    </div>
@endif
