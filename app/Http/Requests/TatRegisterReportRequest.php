<?php

namespace App\Http\Requests;

use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Models\TatRegister;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TatRegisterReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', TatRegister::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->isBnnk()) {
            $this->merge(['bnnk_id' => null]);
        }

        if ($this->has('search')) {
            $this->merge(['search' => trim((string) $this->input('search'))]);
        }
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'bnnk_id' => ['nullable', 'integer', Rule::exists('bnnks', 'id')],
            'recommendation' => ['nullable', Rule::enum(TatRecommendation::class)],
            'execution' => ['nullable', Rule::enum(RecommendationExecution::class)],
            'narcotic_type_id' => ['nullable', 'integer', Rule::exists('narcotic_types', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }
}
