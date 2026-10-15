<?php

namespace App\Http\Requests;

use App\Models\Bnnk;
use Illuminate\Foundation\Http\FormRequest;

class StoreBnnkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBnnp() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $name = strtolower((string) $this->input('name'));

            if ($name !== '' && Bnnk::query()->whereRaw('LOWER(name) = ?', [$name])->exists()) {
                $validator->errors()->add('name', 'BNNK dengan nama tersebut sudah terdaftar.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama unit wajib diisi.',
            'name.max' => 'Nama unit maksimal 255 karakter.',
        ];
    }
}
