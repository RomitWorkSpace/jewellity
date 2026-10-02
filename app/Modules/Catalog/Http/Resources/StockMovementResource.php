<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockMovement */
class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'quantity_change' => $this->quantity_change,
            'quantity_after' => $this->quantity_after,
            'reason' => $this->reason,
            'note' => $this->note,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
        ];
    }
}
