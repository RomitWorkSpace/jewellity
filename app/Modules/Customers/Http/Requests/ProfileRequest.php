<?php

namespace App\Modules\Customers\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? (Phone::normalize($this->input('phone')) ?? $this->input('phone')) : null,
        ]);
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            // Changing the email address (the sign-in identity) needs the current password.
            'current_password' => [Rule::requiredIf(fn () => $this->input('email') !== $user->email), 'nullable', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Enter a valid 10-digit Indian mobile number.'];
    }
}
