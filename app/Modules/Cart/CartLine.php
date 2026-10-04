<?php

namespace App\Modules\Cart;

/** One priced, validated line of the bag. Amounts are integer minor units. */
final class CartLine
{
    public function __construct(
        public readonly int $variantId,
        public readonly string $name,
        public readonly string $url,
        public readonly ?string $image,
        public readonly ?string $imagePath,
        public readonly string $sku,
        public readonly string $options,
        public readonly int $quantity,
        public readonly int $unitPrice,
        public readonly ?int $unitCompare,
        /** Units that can be bought at once; null = no stock limit. */
        public readonly ?int $maxQuantity,
        /** GST % contained in the price; null = shop default. */
        public readonly ?float $gstRate = null,
    ) {}

    public function total(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function savings(): int
    {
        return $this->unitCompare ? ($this->unitCompare - $this->unitPrice) * $this->quantity : 0;
    }

    public function atStockLimit(): bool
    {
        return $this->maxQuantity !== null && $this->quantity >= $this->maxQuantity;
    }

    public function lowStock(): bool
    {
        return $this->maxQuantity !== null && $this->maxQuantity <= 5;
    }
}
