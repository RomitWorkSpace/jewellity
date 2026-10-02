<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'image' => ['required', 'image', 'mimes:'.implode(',', config('catalog.images.mimes')), 'max:'.config('catalog.images.max_kb')],
            'alt' => ['nullable', 'string', 'max:200'],
            'product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product?->id)],
        ];
    }
}
