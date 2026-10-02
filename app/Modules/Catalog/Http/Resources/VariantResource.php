<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class VariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            // Money is in integer minor units (see `currency` on the product).
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'cost_price' => $this->cost_price,
            'weight_grams' => $this->weight_grams,
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'track_inventory' => $this->track_inventory,
            'allow_backorder' => $this->allow_backorder,
            'in_stock' => $this->isInStock(),
            'low_stock' => $this->isLowStock(),
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'attributes' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->map(fn ($v) => [
                'attribute' => $v->attribute?->name,
                'attribute_id' => $v->attribute_id,
                'value_id' => $v->id,
                'value' => $v->value,
            ])),
        ];
    }
}
