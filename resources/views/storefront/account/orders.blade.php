<x-layouts.storefront>
    <x-slot:seo><x-seo title="My orders – {{ config('app.name') }}" :noindex="true" /></x-slot:seo>
    <div class="container-x py-8 sm:py-12">
        <h1 class="font-serif text-3xl font-semibold text-royal-950 sm:text-4xl">My orders</h1>
        <div class="mt-8 grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-12">
            @include('storefront.account._nav')
            <div class="min-w-0">
                @include('storefront.account._flash')
                @if ($orders->isEmpty())
                    <div class="rounded-3xl border border-dashed border-gold-400/60 bg-gradient-to-br from-royal-50 to-gold-50 px-6 py-14 text-center">
                        <p class="font-serif text-2xl text-royal-900">No orders yet</p>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-royal-800/70">When you place an order it will appear here.</p>
                        <a href="{{ url('/shop?sort=newest') }}" class="btn-gold mt-6">Start shopping</a>
                    </div>
                @else
                    <ul class="divide-y divide-royal-100 rounded-2xl border border-royal-100 bg-white px-3 sm:px-4">
                        @foreach ($orders as $order)
                            @include('storefront.account._order-row', ['order' => $order])
                        @endforeach
                    </ul>
                    <div class="mt-6">{{ $orders->links('pagination.storefront') }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.storefront>
