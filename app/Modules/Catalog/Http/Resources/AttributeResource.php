<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attribute */
class AttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($v) => [
                'id' => $v->id, 'value' => $v->value, 'slug' => $v->slug, 'sort_order' => $v->sort_order,
            ])),
        ];
    }
}
