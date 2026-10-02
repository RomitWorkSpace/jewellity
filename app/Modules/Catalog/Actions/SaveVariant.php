<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\StockReason;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class SaveVariant
{
    public function __construct(private AdjustStock $adjustStock) {}

    public function create(Product $product, array $data, ?int $userId = null): ProductVariant
    {
        return DB::transaction(function () use ($product, $data, $userId) {
            $initial = (int) ($data['stock_quantity'] ?? 0);
            $makeDefault = ! empty($data['is_default']) || ! $product->variants()->exists();

            if ($makeDefault) {
                $product->variants()->update(['is_default' => false]);
            }

            $variant = $product->variants()->create(
                collect($data)->except(['stock_quantity', 'attribute_value_ids'])
                    ->merge(['is_default' => $makeDefault, 'stock_quantity' => 0])->all()
            );
            $variant->attributeValues()->sync($data['attribute_value_ids'] ?? []);

            if ($initial !== 0) {
                $this->adjustStock->handle($variant, $initial, StockReason::Initial, 'Initial stock', $userId);
            }

            return $variant->load('attributeValues.attribute');
        });
    }

    /** Stock is intentionally not editable here — use AdjustStock so it's ledgered. */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            if (! empty($data['is_default'])) {
                ProductVariant::where('product_id', $variant->product_id)
                    ->whereKeyNot($variant->id)->update(['is_default' => false]);
            }

            // The default variant can only be moved by promoting another one.
            if ($variant->is_default && array_key_exists('is_default', $data) && ! $data['is_default']) {
                unset($data['is_default']);
            }

            $variant->update(collect($data)->except(['stock_quantity', 'attribute_value_ids'])->all());

            if (array_key_exists('attribute_value_ids', $data)) {
                $variant->attributeValues()->sync($data['attribute_value_ids']);
            }

            return $variant->load('attributeValues.attribute');
        });
    }

    /** Deletes a variant, promoting another to default when needed. */
    public function delete(ProductVariant $variant): void
    {
        DB::transaction(function () use ($variant) {
            $wasDefault = $variant->is_default;
            $variant->delete();

            if ($wasDefault) {
                ProductVariant::where('product_id', $variant->product_id)
                    ->orderBy('sort_order')->orderBy('id')->first()
                    ?->update(['is_default' => true]);
            }
        });
    }
}
