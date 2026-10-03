<?php

return [
    // Text shown in the announcement bar above the header. Set to null/empty to hide it.
    'announcement' => env('STOREFRONT_ANNOUNCEMENT', 'Complimentary gift packaging on every order'),

    // Reassurance strip on the home page. Edit to match your real policies.
    'perks' => [
        ['icon' => 'truck', 'title' => 'Fast delivery', 'text' => 'Carefully packed and shipped to your door'],
        ['icon' => 'shield', 'title' => 'Secure payments', 'text' => 'Your details are always protected'],
        ['icon' => 'refresh', 'title' => 'Easy returns', 'text' => 'Hassle-free if it is not quite right'],
        ['icon' => 'sparkle', 'title' => 'Made to shine', 'text' => 'Skin-friendly, long-lasting finishes'],
    ],

    // Social profiles shown in the footer. Leave a value null to hide that icon.
    'social' => [
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'facebook' => env('SOCIAL_FACEBOOK'),
        'pinterest' => env('SOCIAL_PINTEREST'),
    ],

    'listing' => [
        'per_page' => (int) env('STOREFRONT_PER_PAGE', 24),
    ],

    'home' => [
        'new_arrivals' => 8,
        'featured' => 4,
        'max_nav_categories' => 6,
    ],
];
