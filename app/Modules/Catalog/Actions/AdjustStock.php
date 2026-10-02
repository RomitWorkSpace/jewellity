<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\StockReason;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock changes. Row-locked and ledgered, so concurrent
 * orders/adjustments can't oversell or lose a movement.
 */
class AdjustStock
{
    public function handle(
        ProductVariant $variant,
        int $change,
        StockReason $reason,
        ?string $note = null,
        ?int $userId = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($variant, $change, $reason, $note, $userId, $reference) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $after = $locked->stock_quantity + $change;

            if ($after < 0 && ! $locked->allow_backorder) {
                throw ValidationException::withMessages([
                    'quantity_change' => "Insufficient stock: {$locked->stock_quantity} available.",
                ]);
            }

            $locked->update(['stock_quantity' => $after]);
            $variant->setRawAttributes($locked->getAttributes(), true);

            return StockMovement::create([
                'product_variant_id' => $locked->id,
                'quantity_change' => $change,
                'quantity_after' => $after,
                'reason' => $reason,
                'note' => $note,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'user_id' => $userId,
            ]);
        });
    }
}
