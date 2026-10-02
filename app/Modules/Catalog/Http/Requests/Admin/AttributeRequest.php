<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attribute = $this->route('attribute');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('attributes', 'name')->ignore($attribute)],
            'values' => ['nullable', 'array', 'max:200'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'values.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
