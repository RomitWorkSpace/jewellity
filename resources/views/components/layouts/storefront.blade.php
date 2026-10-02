<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{ $seo ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-neutral-900 antialiased">
    <header class="border-b border-neutral-200">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <a href="{{ url('/') }}" class="text-xl font-semibold tracking-wide">{{ config('app.name') }}</a>
            <button class="md:hidden" @click="$store.ui.toggle('mobileMenu')" aria-label="Menu">☰</button>
        </div>
    </header>
    <main>{{ $slot }}</main>
</body>
</html>
