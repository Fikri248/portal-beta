<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Support\BnnEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBnnp() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! BnnEmail::hasRequiredDomain($value)) {
                        $fail('Email harus menggunakan domain @bnn.go.id.');
                    }

                    if (! BnnEmail::hasValidLocalPart(BnnEmail::localPart($value))) {
                        $fail('Format email tidak valid.');
                    }
                },
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'bnnk_id' => ['nullable', 'integer', 'exists:bnnks,id', 'required_if:role,bnnk'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => BnnEmail::normalize($this->input('email')),
            'role' => UserRole::Bnnk->value,
        ]);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
