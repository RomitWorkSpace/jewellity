<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Enums\StockReason;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(private AdjustStock $adjustStock) {}

    /** @param array<string, mixed> $data validated product payload incl. `variants` and `category_ids` */
    public function handle(array $data, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($data, $userId) {
            $variants = $data['variants'];
            $categoryIds = $data['category_ids'] ?? [];

            $product = Product::create(collect($data)->except(['variants', 'category_ids'])->all());

            // Exactly one default: the flagged one, else the first.
            $defaultIndex = collect($variants)->search(fn ($v) => ! empty($v['is_default']));
            $defaultIndex = $defaultIndex === false ? 0 : $defaultIndex;

            foreach ($variants as $i => $v) {
                $initial = (int) ($v['stock_quantity'] ?? 0);
                $variant = $product->variants()->create(
                    collect($v)->except(['stock_quantity', 'attribute_value_ids'])
                        ->merge(['is_default' => $i === $defaultIndex, 'sort_order' => $i, 'stock_quantity' => 0])
                        ->all()
                );
                $variant->attributeValues()->sync($v['attribute_value_ids'] ?? []);

                if ($initial !== 0) {
                    $this->adjustStock->handle($variant, $initial, StockReason::Initial, 'Initial stock', $userId);
                }
            }

            $product->categories()->sync($categoryIds);

            if ($product->status === ProductStatus::Active && ! $product->published_at) {
                $product->update(['published_at' => now()]);
            }

            return $product->load(['variants.attributeValues.attribute', 'categories', 'images']);
        });
    }
}
