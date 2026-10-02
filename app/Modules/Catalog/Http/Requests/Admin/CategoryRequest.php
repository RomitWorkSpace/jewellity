<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // enforced by route permission middleware
    }

    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash:ascii', Rule::unique('categories', 'slug')->ignore($category)],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at'), function ($attr, $value, $fail) use ($category) {
                if ($category && ($value == $category->id || in_array((int) $value, $category->descendantIds(), true))) {
                    $fail('A category cannot be moved under itself or one of its descendants.');
                }
            }],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ];
    }
}
