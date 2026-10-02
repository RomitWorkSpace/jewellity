@props(['title' => config('app.name'), 'description' => null, 'image' => null, 'type' => 'website', 'canonical' => null, 'jsonLd' => null, 'noindex' => false])
<title>{{ $title }}</title>
@if ($description)<meta name="description" content="{{ $description }}">@endif
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">
@if ($noindex)<meta name="robots" content="noindex,nofollow">@endif
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
@if ($description)<meta property="og:description" content="{{ $description }}">@endif
<meta property="og:url" content="{{ $canonical ?? url()->current() }}">
@if ($image)<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
@if ($jsonLd)<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>@endif
