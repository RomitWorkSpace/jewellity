<?php

namespace App\Modules\Customers\Http\Requests;

use App\Support\IndianStates;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone'),
            'pincode' => preg_replace('/\s+/', '', (string) $this->input('pincode')),
        ]);
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'line1' => ['required', 'string', 'max:200'],
            'line2' => ['nullable', 'string', 'max:200'],
            'landmark' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', Rule::in(IndianStates::all())],
            'pincode' => ['required', 'regex:/^[1-9]\d{5}$/'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the name of the person receiving the order.',
            'phone.required' => 'Please enter a mobile number for delivery updates.',
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'line1.required' => 'Please enter the house / flat number and street.',
            'city.required' => 'Please enter the city or town.',
            'state.required' => 'Please choose a state.',
            'state.in' => 'Please choose a state from the list.',
            'pincode.required' => 'Please enter the 6-digit pincode.',
            'pincode.regex' => 'Enter a valid 6-digit pincode.',
        ];
    }
}
