<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Support\BnnEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBnnp() ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
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
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->route('user');

        $this->merge([
            'email' => BnnEmail::normalize($this->input('email')),
            'role' => $user?->role?->value,
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
