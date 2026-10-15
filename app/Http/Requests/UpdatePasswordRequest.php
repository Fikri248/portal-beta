<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('password') && $this->has('new_password')) {
            $this->merge(['password' => $this->input('new_password')]);
        }

        if (! $this->has('password_confirmation') && $this->has('new_password_confirmation')) {
            $this->merge(['password_confirmation' => $this->input('new_password_confirmation')]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
