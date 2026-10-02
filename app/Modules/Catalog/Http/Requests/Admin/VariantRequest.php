<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ProductVariant|null $variant */
        $variant = $this->route('variant');

        $rules = $this->variantRules($variant);

        // Stock changes on existing variants go through the stock-adjustment endpoint.
        if ($variant) {
            unset($rules['stock_quantity']);
            foreach ($rules as $key => $rule) {
                if (str_contains($key, '.') === false && in_array('required', $rule, true)) {
                    $rules[$key] = array_map(fn ($r) => $r === 'required' ? 'sometimes' : $r, $rule);
                }
            }
        }

        return $rules;
    }

    /** Shared with ProductRequest (nested variants). @return array<string, array<mixed>> */
    public function variantRules(?ProductVariant $variant = null, ?FormRequest $source = null): array
    {
        $source ??= $this;

        return [
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variant)],
            'barcode' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:99999999999'],
            'compare_at_price' => ['nullable', 'integer', 'min:0', 'max:99999999999', $this->comparePriceRule($variant, $source)],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:99999999999'],
            'weight_grams' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'track_inventory' => ['nullable', 'boolean'],
            'allow_backorder' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'distinct', 'exists:attribute_values,id'],
        ];
    }

    /** compare_at_price must exceed the (sibling) price; works for nested variants.*.price too. */
    private function comparePriceRule(?ProductVariant $variant, FormRequest $source): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($variant, $source) {
            if ($value === null) {
                return;
            }

            $price = $source->input(preg_replace('/compare_at_price$/', 'price', $attribute));
            $price ??= $variant?->price;

            if ($price !== null && (int) $value <= (int) $price) {
                $fail('The compare at price must be greater than the price.');
            }
        };
    }
}
