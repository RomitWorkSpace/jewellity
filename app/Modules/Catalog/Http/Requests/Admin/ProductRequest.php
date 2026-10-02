<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create (with variants) and update (product-level fields only). */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $creating = $product === null;

        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash:ascii', Rule::unique('products', 'slug')->ignore($product)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:65000'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'is_featured' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ];

        if ($creating) {
            $rules['variants'] = ['required', 'array', 'min:1', 'max:100'];
            foreach ((new VariantRequest)->variantRules(source: $this) as $key => $rule) {
                $rules["variants.*.$key"] = $rule;
            }
            $rules['variants.*.sku'][] = 'distinct';
        }

        return $rules;
    }
}
