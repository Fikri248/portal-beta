<?php

namespace App\Http\Requests;

use App\Models\TatRegister;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchTatRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', TatRegister::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => is_string($this->input('mode')) ? trim($this->input('mode')) : $this->input('mode'),
            'nik' => is_string($this->input('nik')) ? trim($this->input('nik')) : $this->input('nik'),
            'nama' => is_string($this->input('nama')) ? trim($this->input('nama')) : $this->input('nama'),
            'tanggal_lahir' => is_string($this->input('tanggal_lahir')) ? trim($this->input('tanggal_lahir')) : $this->input('tanggal_lahir'),
        ]);
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(['nik', 'name_dob'])],
            'nik' => ['nullable', 'string'],
            'nama' => ['nullable', 'string'],
            'tanggal_lahir' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('mode') && ! $this->filled('nik') && ! $this->filled('nama') && ! $this->filled('tanggal_lahir')) {
                return;
            }

            $mode = $this->input('mode');

            if (! $mode) {
                $validator->errors()->add('mode', 'Pilih metode pencarian.');

                return;
            }

            if ($mode === 'nik' && blank($this->input('nik'))) {
                $validator->errors()->add('nik', 'NIK wajib diisi untuk pencarian berdasarkan NIK.');
            }

            if ($mode === 'name_dob') {
                if (blank($this->input('nama'))) {
                    $validator->errors()->add('nama', 'Nama wajib diisi untuk pencarian ini.');
                }

                if (blank($this->input('tanggal_lahir'))) {
                    $validator->errors()->add('tanggal_lahir', 'Tanggal lahir wajib diisi untuk pencarian ini.');
                }
            }
        });
    }
}
