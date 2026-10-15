<?php

namespace App\Http\Requests;

use App\Models\TatRegister;
use Illuminate\Foundation\Http\FormRequest;

class UploadTatRegisterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isBnnk() ?? false) && ($user->can('create', TatRegister::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'bnnk_id' => [
                'prohibited',
            ],
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:xlsx',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'bnnk_id' => 'BNNK tujuan',
            'file' => 'file Excel',
        ];
    }
}
