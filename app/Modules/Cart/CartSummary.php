<?php

namespace App\Modules\Cart;

final class CartSummary
{
    /**
     * @param  list<CartLine>  $lines
     * @param  list<string>  $notices  things that changed since the customer last looked
     */
    public function __construct(public readonly array $lines, public readonly array $notices = []) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function count(): int
    {
        return array_sum(array_map(fn (CartLine $l) => $l->quantity, $this->lines));
    }

    public function subtotal(): int
    {
        return array_sum(array_map(fn (CartLine $l) => $l->total(), $this->lines));
    }

    /** How much cheaper the bag is than the compare-at ("was") prices. */
    public function savings(): int
    {
        return array_sum(array_map(fn (CartLine $l) => $l->savings(), $this->lines));
    }
}
