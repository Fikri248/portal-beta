<?php

namespace App\Services;

use App\Enums\{AuditLogAction, Gender, RecommendationExecution, TatRecommendation, TatRegisterStatus, UserRole};
use App\Models\{AuditLog, Bnnk, TatRegister, User};
use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /** @var array<string, bool> */
    private static array $suppressionStack = [];

    /** @var array<int, string> */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'session',
        '_token',
        'csrf_token',
        'file',
        'uploaded_file',
        'photo_binary',
        'foto_klien',
        'photo',
        'photo_path',
        'binary',
        'absolute_path',
        'storage_path',
        'private_path',
    ];

    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'name' => 'Nama',
        'email' => 'Email',
        'role' => 'Role',
        'bnnk_id' => 'BNNK',
        'is_active' => 'Status akun',
        'status' => 'Status',
        'no' => 'NO',
        'no_bln' => 'NO/BLN',
        'no_register' => 'NO Register',
        'asal_pengajuan' => 'Asal Pengajuan',
        'tanggal_surat' => 'Tanggal Surat',
        'tanggal_berkas_diterima' => 'Tanggal Berkas Diterima',
        'tanggal_pelaksanaan' => 'Tanggal Pelaksanaan',
        'no_surat_pengajuan' => 'No Surat Pengajuan',
        'no_lkn_lp_li' => 'No LKN/LP/LI',
        'pasal_yang_disangkakan' => 'Pasal yang Disangkakan',
        'tanggal_penangkapan' => 'Tanggal Penangkapan',
        'nama' => 'Nama Klien',
        'alamat_ktp' => 'Alamat KTP',
        'alamat_domisili' => 'Alamat Domisili',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'jenis_kelamin' => 'Jenis Kelamin',
        'usia' => 'Usia',
        'pendidikan' => 'Pendidikan',
        'pekerjaan' => 'Pekerjaan',
        'no_handphone' => 'No Handphone',
        'nik' => 'NIK',
        'hasil_asesmen_hukum' => 'Hasil Asesmen Hukum',
        'hasil_asesmen_medis' => 'Hasil Asesmen Medis',
        'rekomendasi_tat' => 'Rekomendasi TAT',
        'pelaksanaan_rekomendasi' => 'Pelaksanaan Rekomendasi',
        'hasil_rekom_tat' => 'Hasil Rekom TAT',
        'keterangan' => 'Keterangan',
        'narcotics' => 'Data Narkotika',
        'target_bnnk_id' => 'BNNK Tujuan',
        'row_count' => 'Jumlah Baris',
    ];

    /**
     * @param  AuditLogAction|string  $action
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        AuditLogAction|string $action,
        ?Model $entity = null,
        ?int $bnnkId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?string $description = null,
        ?User $actor = null,
    ): ?AuditLog {
        if (self::isSuppressed()) {
            return null;
        }

        $actionValue = $action instanceof AuditLogAction ? $action->value : $action;
        $actionEnum = AuditLogAction::tryFrom($actionValue);
        $actor ??= Auth::user();
        $request = app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'user_id' => $actor?->getKey(),
            'bnnk_id' => $bnnkId ?? $this->resolveBnnkId($actor, $entity),
            'action' => $actionValue,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description ?? $actionEnum?->label(),
            'old_values' => $this->sanitizeArray($oldValues),
            'new_values' => $this->sanitizeArray($newValues),
            'metadata' => $this->sanitizeArray($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /** @param Closure(): mixed $callback */
    public static function withoutAudit(Closure $callback): mixed
    {
        $token = bin2hex(random_bytes(8));
        self::$suppressionStack[$token] = true;

        try {
            return $callback();
        } finally {
            unset(self::$suppressionStack[$token]);
        }
    }

    public static function isSuppressed(): bool
    {
        return self::$suppressionStack !== [];
    }

    /** @param array<string, mixed> $oldValues @param array<string, mixed> $newValues */
    public function changedValues(array $oldValues, array $newValues): array
    {
        $old = $this->sanitizeArray($oldValues) ?? [];
        $new = $this->sanitizeArray($newValues) ?? [];
        $oldChanged = [];
        $newChanged = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            if (($old[$key] ?? null) === ($new[$key] ?? null)) {
                continue;
            }

            if (array_key_exists($key, $old)) {
                $oldChanged[$key] = $old[$key];
            }

            if (array_key_exists($key, $new)) {
                $newChanged[$key] = $new[$key];
            }
        }

        return ['old' => $oldChanged, 'new' => $newChanged];
    }

    /** @return array<string, mixed> */
    public function tatSnapshot(TatRegister $record): array
    {
        $record->loadMissing('narcotics.narcoticType', 'narcotics.unit');
        $fields = [
            'status', 'no', 'no_bln', 'no_bln_month', 'no_bln_year', 'asal_pengajuan',
            'tanggal_surat', 'tanggal_berkas_diterima', 'tanggal_pelaksanaan',
            'no_surat_pengajuan', 'no_lkn_lp_li', 'pasal_yang_disangkakan',
            'tanggal_penangkapan', 'nama', 'no_register', 'alamat_ktp', 'alamat_domisili',
            'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'usia', 'pendidikan',
            'pekerjaan', 'no_handphone', 'nik', 'hasil_asesmen_hukum', 'hasil_asesmen_medis',
            'rekomendasi_tat', 'pelaksanaan_rekomendasi', 'hasil_rekom_tat', 'keterangan',
        ];

        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = $record->getAttribute($field);
        }

        $snapshot['narcotics'] = $record->narcotics->map(fn ($narcotic): array => [
            'narcotic_type' => $narcotic->narcoticType?->name,
            'jumlah' => $narcotic->jumlah,
            'unit' => $narcotic->unit?->name,
        ])->values()->all();

        return $this->sanitizeArray($snapshot) ?? [];
    }

    /** @return array<string, mixed> */
    public function userSnapshot(User $user): array
    {
        return $this->sanitizeArray([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'bnnk_id' => $user->bnnk_id,
            'is_active' => $user->is_active,
        ]) ?? [];
    }

    /** @return array<int, array{field: string, label: string, old: string, new: string}> */
    public function presentChanges(AuditLog $log): array
    {
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];
        $changes = [];

        foreach (array_unique(array_merge(array_keys($oldValues), array_keys($newValues))) as $field) {
            $changes[] = [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'old' => $this->displayValue($field, $oldValues[$field] ?? null),
                'new' => $this->displayValue($field, $newValues[$field] ?? null),
            ];
        }

        return $changes;
    }

    /** @return array<int, array{label: string, value: string}> */
    public function presentMetadata(AuditLog $log): array
    {
        $metadata = $log->metadata ?? [];
        $presented = [];

        foreach ($metadata as $field => $value) {
            if ($field === 'filter_summary' && is_array($value)) {
                $value = collect($value)
                    ->map(fn ($item, $key): string => $this->fieldLabel((string) $key).': '.$this->displayValue((string) $key, $item))
                    ->implode(' · ');
            } else {
                $value = $this->displayValue((string) $field, $value);
            }

            $presented[] = [
                'label' => $this->fieldLabel((string) $field),
                'value' => $value,
            ];
        }

        return $presented;
    }

    public function entityLabel(?string $entityType): string
    {
        return match ($entityType) {
            'TatRegister' => 'Register TAT',
            'User' => 'User',
            default => $entityType ?: '—',
        };
    }

    public function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? str($field)->replace('_', ' ')->title()->toString();
    }

    public function displayValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'bnnk_id' || $field === 'target_bnnk_id') {
            return Bnnk::query()->find($value)?->name ?? (string) $value;
        }

        if ($field === 'status') {
            return TatRegisterStatus::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($field === 'rekomendasi_tat') {
            return TatRecommendation::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($field === 'pelaksanaan_rekomendasi') {
            return RecommendationExecution::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($field === 'jenis_kelamin') {
            return Gender::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($field === 'role') {
            return UserRole::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($field === 'is_active') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Aktif' : 'Nonaktif';
        }

        if ($field === 'nik') {
            return $this->maskNik((string) $value);
        }

        if (is_array($value)) {
            return collect($value)->map(function ($item, $key) use ($field): string {
                if (is_array($item)) {
                    return collect($item)->map(fn ($nestedValue, $nestedKey) => $this->fieldLabel((string) $nestedKey).': '.$this->displayValue((string) $nestedKey, $nestedValue))->implode(', ');
                }

                return $this->displayValue($field.'.'.$key, $item);
            })->implode('; ');
        }

        if (str_contains($field, 'tanggal') && is_string($value)) {
            try {
                return Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                // Keep non-date values as-is.
            }
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        return (string) $value;
    }

    /** @return array<string, mixed>|null */
    private function sanitizeArray(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $result = [];
        foreach ($values as $key => $value) {
            $normalizedKey = is_string($key) ? $key : (string) $key;
            if ($this->isSensitiveKey($normalizedKey) || $value instanceof UploadedFile) {
                continue;
            }

            if (is_array($value)) {
                $result[$normalizedKey] = $this->sanitizeArray($value) ?? [];
                continue;
            }

            if ($value instanceof BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value instanceof Carbon ? $value->toDateString() : $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $value = method_exists($value, '__toString') ? (string) $value : null;
            }

            if (strtolower($normalizedKey) === 'nik' && $value !== null) {
                $value = $this->maskNik((string) $value);
            }

            $result[$normalizedKey] = $value;
        }

        return $result;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        return in_array($normalized, self::SENSITIVE_KEYS, true)
            || str_contains($normalized, 'password')
            || str_contains($normalized, 'binary')
            || str_contains($normalized, 'absolute_path');
    }

    private function maskNik(string $value): string
    {
        $value = trim($value);

        return strlen($value) <= 4 ? $value : str_repeat('*', strlen($value) - 4).substr($value, -4);
    }

    private function resolveBnnkId(?User $actor, ?Model $entity): ?int
    {
        if ($entity instanceof TatRegister || $entity instanceof User) {
            return $entity->bnnk_id ? (int) $entity->bnnk_id : null;
        }

        return $actor?->bnnk_id ? (int) $actor->bnnk_id : null;
    }
}
