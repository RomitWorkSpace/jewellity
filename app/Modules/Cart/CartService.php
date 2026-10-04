<?php

namespace App\Modules\Cart;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Session-backed bag: [variant_id => quantity]. Only ids and quantities are stored; prices, names and
 * stock are read fresh every time, so the bag can never show or charge a stale price.
 * When customer accounts and checkout arrive, persist the same shape for signed-in customers.
 */
class CartService
{
    private const KEY = 'cart.lines';

    public function __construct(private Session $session) {}

    /** @return array<int, int> variant id => quantity */
    public function lines(): array
    {
        return array_map('intval', (array) $this->session->get(self::KEY, []));
    }

    public function count(): int
    {
        return array_sum($this->lines());
    }

    /** The variant a customer is allowed to buy right now, or null. */
    public function purchasable(int $variantId): ?ProductVariant
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($p) => $p->published())
            ->find($variantId);
    }

    /**
     * Adds units, enforcing stock and the per-line cap. Returns the resulting line quantity.
     *
     * @throws ValidationException
     */
    public function add(ProductVariant $variant, int $quantity): int
    {
        $lines = $this->lines();
        $wanted = ($lines[$variant->id] ?? 0) + $quantity;

        $this->assertQuantityAllowed($variant, $wanted, $lines[$variant->id] ?? 0);

        $lines[$variant->id] = $wanted;
        $this->session->put(self::KEY, $lines);

        return $wanted;
    }

    /**
     * Sets a line's quantity.
     *
     * @throws NotFoundHttpException when the item is not in the bag
     * @throws ValidationException when the item is unavailable or the quantity not allowed
     */
    public function update(int $variantId, int $quantity): void
    {
        $lines = $this->lines();
        if (! isset($lines[$variantId])) {
            throw new NotFoundHttpException('That item is not in your bag.');
        }

        $variant = $this->purchasable($variantId);
        if (! $variant) {
            $this->remove($variantId);
            throw ValidationException::withMessages(['quantity' => 'This item is no longer available and was removed from your bag.']);
        }

        $this->assertQuantityAllowed($variant, $quantity, 0);

        $lines[$variantId] = $quantity;
        $this->session->put(self::KEY, $lines);
    }

    public function remove(int $variantId): void
    {
        $lines = $this->lines();
        if (! isset($lines[$variantId])) {
            throw new NotFoundHttpException('That item is not in your bag.');
        }

        unset($lines[$variantId]);
        $this->session->put(self::KEY, $lines);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    /**
     * The bag as it stands right now: current prices and stock applied. Anything that is no longer
     * sellable is dropped and quantities are trimmed to what is available (with a notice), and the
     * session is corrected so the customer is told once and the bag stays truthful.
     */
    public function summary(): CartSummary
    {
        $stored = $this->lines();
        if ($stored === []) {
            return new CartSummary([]);
        }

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($p) => $p->published())
            ->with(['product.images', 'attributeValues.attribute'])
            ->whereIn('id', array_keys($stored))
            ->get()->keyBy('id');

        $missing = array_diff(array_keys($stored), $variants->keys()->all());
        $removedNames = $missing
            ? ProductVariant::withTrashed()->with(['product' => fn ($q) => $q->withTrashed()])->whereIn('id', $missing)->get()->keyBy('id')
            : collect();

        $lines = [];
        $notices = [];
        $clean = [];

        foreach ($stored as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if (! $variant) {
                $name = $removedNames->get($variantId)?->product?->name;
                $notices[] = ($name ? "“{$name}”" : 'An item').' is no longer available and was removed from your bag.';

                continue;
            }

            $name = $variant->product->name;
            $limited = $variant->track_inventory && ! $variant->allow_backorder;

            if ($limited && $variant->stock_quantity <= 0) {
                $notices[] = "“{$name}” is now out of stock and was removed from your bag.";

                continue;
            }

            $allowed = min(config('cart.max_per_line'), $limited ? $variant->stock_quantity : PHP_INT_MAX);
            if ($quantity > $allowed) {
                $notices[] = "Only {$allowed} of “{$name}” ".($allowed === 1 ? 'is' : 'are').' available, so the quantity was updated.';
                $quantity = $allowed;
            }

            $clean[$variantId] = $quantity;
            $lines[] = $this->line($variant, $quantity, $limited);
        }

        if ($clean !== $stored) {
            $this->session->put(self::KEY, $clean);
        }

        return new CartSummary($lines, $notices);
    }

    private function line(ProductVariant $variant, int $quantity, bool $limited): CartLine
    {
        $product = $variant->product;
        $image = $product->images->firstWhere('product_variant_id', $variant->id) ?? $product->images->first();

        return new CartLine(
            variantId: $variant->id,
            name: $product->name,
            url: url('/product/'.$product->slug.($product->variants()->count() > 1 ? '?variant='.$variant->id : '')),
            image: $image?->url(),
            imagePath: $image?->path,
            sku: $variant->sku,
            options: $variant->attributeValues->sortBy('attribute.name')
                ->map(fn ($v) => $v->attribute->name.': '.$v->value)->implode(' · '),
            quantity: $quantity,
            unitPrice: $variant->price,
            unitCompare: $variant->compare_at_price && $variant->compare_at_price > $variant->price ? $variant->compare_at_price : null,
            maxQuantity: $limited ? $variant->stock_quantity : null,
            gstRate: $product->gst_rate,
        );
    }

    /** @throws ValidationException */
    private function assertQuantityAllowed(ProductVariant $variant, int $wanted, int $alreadyInBag): void
    {
        $max = config('cart.max_per_line');

        if ($variant->track_inventory && ! $variant->allow_backorder) {
            if ($variant->stock_quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Sorry, this item is out of stock.']);
            }
            if ($wanted > $variant->stock_quantity) {
                throw ValidationException::withMessages(['quantity' => $alreadyInBag > 0
                    ? "Only {$variant->stock_quantity} available, and you already have {$alreadyInBag} in your bag."
                    : "Only {$variant->stock_quantity} available."]);
            }
        }

        if ($wanted > $max) {
            throw ValidationException::withMessages(['quantity' => "You can add up to {$max} of an item."]);
        }
    }
}
