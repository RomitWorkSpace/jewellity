<x-layouts.storefront>
    <x-slot:seo><x-seo title="Sign in – {{ config('app.name') }}" :noindex="true" /></x-slot:seo>
    @component('storefront.account._auth-shell', ['title' => 'Welcome back', 'subtitle' => 'Sign in to see your orders and saved addresses.'])
        <form method="post" action="{{ route('login.attempt') }}" class="space-y-5" novalidate>
            @csrf
            <x-form.field name="email" label="Email" type="email" required autocomplete="username" autofocus />
            <div>
                <x-form.field name="password" label="Password" type="password" required autocomplete="current-password" />
                <p class="mt-2 text-right text-xs"><a href="{{ route('password.request') }}" class="font-semibold text-royal-700 underline underline-offset-2 hover:text-royal-500">Forgot password?</a></p>
            </div>
            <label class="flex items-center gap-2 text-sm text-royal-900"><input type="checkbox" name="remember" value="1" class="size-4 rounded border-royal-300 accent-royal-700"> Keep me signed in</label>
            <button type="submit" class="btn-gold w-full !py-3">Sign in</button>
        </form>
        @slot('footer')
            New here? <a href="{{ route('register') }}" class="font-semibold text-royal-700 underline underline-offset-2 hover:text-royal-500">Create an account</a>
        @endslot
    @endcomponent
</x-layouts.storefront>
