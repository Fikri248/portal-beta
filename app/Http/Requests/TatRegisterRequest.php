<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Enums\TatRegisterStatus;
use App\Models\TatRegister;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TatRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('tatRegister');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', TatRegister::class);
    }

    protected function prepareForValidation(): void
    {
        $record = $this->route('tatRegister');
        $status = $this->input('status');
        if ($status === null) {
            $status = $record?->status?->value ?? TatRegisterStatus::Draft->value;
        }
        $this->merge(['status' => $status]);
        if ($this->exists('narcotics')) {
            $rows = $this->input('narcotics');
            if (is_array($rows)) {
                $rows = array_values(array_filter($rows, fn ($row) => is_array($row) && (
                    filled($row['narcotic_type_id'] ?? null) || filled($row['jumlah'] ?? null) || filled($row['unit_id'] ?? null)
                )));
                $this->merge(['narcotics' => $rows]);
            }
        }
    }

    public function rules(): array
    {
        $isFinal = $this->input('status') === TatRegisterStatus::Final->value;
        $required = $isFinal ? 'required' : 'nullable';

        return [
            'status' => ['required', Rule::enum(TatRegisterStatus::class)], 'bnnk_id' => ['nullable', 'integer', 'exists:bnnks,id'],
            'no' => [$required, 'integer', 'min:1'], 'no_bln' => ['nullable', 'integer', 'min:1'], 'no_bln_month' => ['nullable', 'integer', 'between:1,12'], 'no_bln_year' => ['nullable', 'integer', 'between:2000,2200'], 'asal_pengajuan' => [$required, 'string', 'max:255'], 'tanggal_surat' => [$required, 'date'], 'tanggal_berkas_diterima' => [$required, 'date'], 'tanggal_pelaksanaan' => [$required, 'date'], 'no_surat_pengajuan' => [$required, 'string', 'max:255'], 'no_lkn_lp_li' => [$required, 'string', 'max:255'], 'tanggal_penangkapan' => [$required, 'date'], 'no_register' => [$required, 'string', 'max:255', Rule::unique('tat_registers', 'no_register')->ignore($this->route('tatRegister'))],
            'nama' => [$required, 'string', 'max:255'], 'alamat_ktp' => [$required, 'string'], 'alamat_domisili' => [$required, 'string'], 'tempat_lahir' => [$required, 'string', 'max:255'], 'tanggal_lahir' => [$required, 'date'], 'jenis_kelamin' => [$required, Rule::enum(Gender::class)], 'usia' => [$required, 'integer', 'min:0', 'max:150'], 'pendidikan' => [$required, 'string', 'max:255'], 'pekerjaan' => [$required, 'string', 'max:255'], 'no_handphone' => [$required, 'string', 'max:50'], 'nik' => ['nullable', 'string', 'max:32'], 'foto_klien' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'pasal_yang_disangkakan' => [$required, 'string'],
            'hasil_asesmen_hukum' => [$required, 'string'], 'hasil_asesmen_medis' => [$required, 'string'], 'rekomendasi_tat' => [$required, Rule::enum(TatRecommendation::class)], 'pelaksanaan_rekomendasi' => [$required, Rule::enum(RecommendationExecution::class)], 'hasil_rekom_tat' => [$required, 'string'], 'keterangan' => ['nullable', 'string'],
            'narcotics' => ['nullable', 'array'], 'narcotics.*.narcotic_type_id' => ['required', 'integer', 'exists:narcotic_types,id'], 'narcotics.*.jumlah' => ['nullable', 'numeric', 'min:0'], 'narcotics.*.unit_id' => ['nullable', 'integer', 'exists:units,id'], ];
    }

    public function attributes(): array
    {
        return ['no' => 'NO', 'no_bln' => 'NO/BLN', 'no_register' => 'NO REGISTER', 'pasal_yang_disangkakan' => 'PASAL YANG DISANGKAKAN', 'foto_klien' => 'foto klien', 'narcotics.*.narcotic_type_id' => 'jenis narkotika'];
    }

    public function messages(): array
    {
        return ['foto_klien.image' => 'Foto klien harus berupa gambar.', 'foto_klien.mimes' => 'Foto klien harus berupa file JPG, JPEG, atau PNG.', 'foto_klien.max' => 'Ukuran foto klien maksimal 5 MB.'];
    }
}
