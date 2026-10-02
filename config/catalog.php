<?php

return [
    // Single-store currency. Prices are stored as integer minor units (e.g. paise/cents).
    'currency' => env('SHOP_CURRENCY', 'INR'),

    'images' => [
        'disk' => env('CATALOG_IMAGE_DISK', 'public'),
        'directory' => 'catalog',
        'max_kb' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
