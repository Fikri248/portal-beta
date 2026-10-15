<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\AuditLogAction;
use App\Enums\RecommendationExecution;
use App\Enums\TatRecommendation;
use App\Enums\TatRegisterStatus;
use App\Models\Bnnk;
use App\Models\NarcoticType;
use App\Models\TatRegister;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TatRegisterImportService
{
    public const MAX_FILE_SIZE_KB = 10240;

    private const PREVIEW_TTL_MINUTES = 15;

    private const REQUIRED_HEADERS = [
        'no' => 'NO',
        'no_bln' => 'NO/BLN',
        'asal_pengajuan' => 'ASAL PENGAJUAN',
        'tanggal_surat' => 'TANGGAL SURAT',
        'tanggal_berkas_diterima' => 'TANGGAL BERKAS DITERIMA',
        'tanggal_pelaksanaan' => 'TANGGAL PELAKSANAAN',
        'no_surat_pengajuan' => 'NO SURAT PENGAJUAN',
        'no_lkn_lp_li' => 'NO LKN/LP/LI',
        'pasal_yang_disangkakan' => 'PASAL YANG DISANGKAKAN',
        'tanggal_penangkapan' => 'TGL PENANGKAPAN',
        'nama' => 'NAMA',
        'no_register' => 'NO REGISTER',
        'alamat_ktp' => 'ALAMAT KTP',
        'alamat_domisili' => 'ALAMAT DOMISILI',
        'tempat_lahir' => 'TEMPAT LAHIR',
        'tanggal_lahir' => 'TANGGAL LAHIR',
        'jenis_kelamin' => 'JENIS KELAMIN',
        'usia' => 'USIA',
        'pendidikan' => 'PENDIDIKAN',
        'pekerjaan' => 'PEKERJAAN',
        'no_handphone' => 'NO HANDPHONE',
        'jenis_narkotika' => 'JENIS NARKOTIKA',
        'jumlah' => 'JUMLAH / BERAT',
        'satuan' => 'SATUAN',
        'hasil_asesmen_hukum' => 'HASIL ASESMEN HUKUM',
        'hasil_asesmen_medis' => 'HASIL ASESMEN MEDIS',
        'pelaksanaan_rekomendasi' => 'PELAKSANAAN REKOMENDASI',
        'hasil_rekom_tat' => 'HASIL REKOM TAT',
    ];

    private const OPTIONAL_HEADERS = [
        'nik' => 'NIK',
        'keterangan' => 'KET',
    ];

    private const RECOMMENDATION_COLUMNS = [
        'RAWAT JALAN' => TatRecommendation::RawatJalan,
        'RAWAT INAP' => TatRecommendation::RawatInap,
        'REHAB DI LAPAS/RUTAN' => TatRecommendation::RehabLapasRutan,
        'REHAB LAPAS/RUTAN' => TatRecommendation::RehabLapasRutan,
        'TIDAK REHAB' => TatRecommendation::TidakRehab,
        'TIDAK REHAB / PROSES HUKUM' => TatRecommendation::TidakRehab,
        'TIDAK REHAB/PROSES HUKUM' => TatRecommendation::TidakRehab,
        'TIDAK REHAB (PROSES HUKUM)' => TatRecommendation::TidakRehab,
    ];

    public function __construct(
        private readonly XlsxReader $reader,
        private readonly TatNumberService $tatNumberService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function resolveTargetBnnk(User $user, mixed $requestedBnnkId = null): Bnnk
    {
        if (! $user->isBnnk() || $user->bnnk_id === null) {
            throw new TatRegisterImportException('Import data hanya dapat dilakukan oleh akun BNNK.');
        }

        if ($requestedBnnkId !== null && (int) $requestedBnnkId !== (int) $user->bnnk_id) {
            throw new TatRegisterImportException('BNNK tujuan import harus sama dengan BNNK akun Anda.');
        }

        $bnnk = Bnnk::find($user->bnnk_id);

        if (! $bnnk) {
            throw new TatRegisterImportException('BNNK tujuan import tidak valid.');
        }

        return $bnnk;
    }

    /**
     * @return array{valid: bool, rows: array<int, array<string, mixed>>, errors: array<int, array{row: int, field: string, message: string}>}
     */
    public function validateFile(UploadedFile $file, Bnnk $targetBnnk): array
    {
        try {
            $spreadsheetRows = $this->reader->read($file->getRealPath());
            [$headerMap, $dataStartRow, $headerErrors] = $this->mapHeaders($spreadsheetRows);

            if ($headerErrors) {
                return ['valid' => false, 'rows' => [], 'errors' => $headerErrors];
            }

            $rows = [];
            $errors = [];

            foreach ($spreadsheetRows as $rowNumber => $rawRow) {
                if ($rowNumber <= $dataStartRow || $this->isEmptyRow($rawRow)) {
                    continue;
                }

                $normalized = $this->normalizeRow($rawRow, $rowNumber, $headerMap, $errors, $targetBnnk);

                if ($normalized) {
                    $rows[] = $normalized;
                }
            }

            if (! $rows && ! $errors) {
                $this->addError($errors, max($dataStartRow + 1, 1), 'FILE', 'File tidak berisi baris data Register TAT.');
            }

            $this->validateDuplicatesInsideFile($rows, $errors);
            $this->validateDatabaseDuplicates($rows, $errors);

            return ['valid' => $errors === [], 'rows' => $rows, 'errors' => $errors];
        } catch (Throwable) {
            return [
                'valid' => false,
                'rows' => [],
                'errors' => [[
                    'row' => 1,
                    'field' => 'FILE',
                    'message' => 'File Excel tidak dapat dibaca. Pastikan formatnya adalah .xlsx yang valid.',
                ]],
            ];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function storePreview(User $user, Bnnk $targetBnnk, array $rows): string
    {
        $token = Str::random(64);
        $now = now();
        $payload = [
            'version' => 1,
            'user_id' => $user->id,
            'bnnk_id' => $targetBnnk->id,
            'bnnk_name' => $targetBnnk->name,
            'rows' => $rows,
            'created_at' => $now->timestamp,
            'expires_at' => $now->copy()->addMinutes(self::PREVIEW_TTL_MINUTES)->timestamp,
            'state' => 'valid',
        ];

        Storage::disk('local')->put($this->payloadPath($token), Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)));

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreview(User $user, string $token): array
    {
        $payload = $this->readPayload($user, $token);

        if (! $payload || isset($payload['consumed_at']) || ($payload['state'] ?? 'valid') !== 'valid') {
            throw new TatRegisterImportException('Preview import tidak ditemukan atau sudah digunakan.');
        }

        return $payload;
    }

    public function discardPreview(User $user, string $token): void
    {
        $payload = $this->readPayload($user, $token);

        if ($payload) {
            Storage::disk('local')->delete($this->payloadPath($token));
        }
    }

    public function confirm(User $user, string $token): int
    {
        $lock = Cache::lock('tat-register-import:'.$token, 30);

        return $lock->block(5, function () use ($user, $token): int {
            $payload = $this->readPayload($user, $token);

            if (! $payload || isset($payload['consumed_at']) || ($payload['state'] ?? 'valid') !== 'valid') {
                throw new TatRegisterImportException('Preview import tidak ditemukan atau sudah digunakan.');
            }

            $payload['state'] = 'processing';
            $payload['processing_at'] = now()->timestamp;
            $this->writePayload($token, $payload);

            try {
                DB::transaction(function () use ($payload, $user): void {
                    foreach ($payload['rows'] as $row) {
                        $record = new TatRegister;
                        $record->forceFill($row['register'])->save();

                        foreach ($row['narcotics'] as $narcotic) {
                            $record->narcotics()->create([
                                'narcotic_type_id' => $narcotic['narcotic_type_id'],
                                'jumlah' => $narcotic['jumlah'],
                                'unit_id' => $narcotic['unit_id'],
                            ]);
                        }
                    }

                    $this->auditLogService->record(
                        AuditLogAction::ImportCompleted,
                        bnnkId: (int) $payload['bnnk_id'],
                        metadata: [
                            'target_bnnk_id' => (int) $payload['bnnk_id'],
                            'row_count' => count($payload['rows']),
                        ],
                        actor: $user,
                    );
                });
            } catch (Throwable $exception) {
                $payload['state'] = 'valid';
                unset($payload['processing_at']);
                $this->writePayload($token, $payload);

                throw new TatRegisterImportException('Import gagal diproses. Tidak ada data yang disimpan.', 0, $exception);
            }

            Storage::disk('local')->delete($this->payloadPath($token));

            return count($payload['rows']);
        });
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $spreadsheetRows
     * @return array{0: array<string, mixed>, 1: int, 2: array<int, array{row: int, field: string, message: string}>}
     */
    private function mapHeaders(array $spreadsheetRows): array
    {
        $bestRowNumber = null;
        $bestScore = 0;

        foreach (array_slice($spreadsheetRows, 0, 12, true) as $rowNumber => $row) {
            $score = 0;

            foreach ($row as $value) {
                if ($this->resolveHeader($value) !== null) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRowNumber = $rowNumber;
            }
        }

        if ($bestRowNumber === null || $bestScore < 3) {
            return [[], 0, [[
                'row' => 1,
                'field' => 'HEADER',
                'message' => 'Format file tidak sesuai template Register TAT. Header tidak ditemukan.',
            ]]];
        }

        $headerRow = $spreadsheetRows[$bestRowNumber] ?? [];
        $subHeaderRow = $spreadsheetRows[$bestRowNumber + 1] ?? [];
        $hasSubHeaders = $this->hasRecommendationSubHeaders($subHeaderRow);
        $headerMap = [];
        $recommendationColumns = [];
        $duplicateHeaders = [];
        $maximumColumn = max(array_keys($headerRow ?: [1 => null]));

        if ($hasSubHeaders) {
            $maximumColumn = max($maximumColumn, max(array_keys($subHeaderRow ?: [1 => null])));
        }

        for ($column = 1; $column <= $maximumColumn; $column++) {
            $parent = $this->stringValue($headerRow[$column] ?? null);
            $child = $hasSubHeaders ? $this->stringValue($subHeaderRow[$column] ?? null) : '';
            $canonical = $this->resolveHeader(trim($parent.' '.$child));
            $canonical ??= $this->resolveHeader($parent);
            $canonical ??= $this->resolveHeader($child);

            if ($canonical === null) {
                continue;
            }

            if (str_starts_with($canonical, 'recommendation:')) {
                $recommendationColumns[substr($canonical, 15)] = $column;

                continue;
            }

            if (isset($headerMap[$canonical])) {
                $duplicateHeaders[] = self::REQUIRED_HEADERS[$canonical] ?? $canonical;

                continue;
            }

            $headerMap[$canonical] = $column;
        }

        $errors = [];

        foreach (self::REQUIRED_HEADERS as $field => $label) {
            if (! isset($headerMap[$field])) {
                $this->addError($errors, $bestRowNumber, $label, 'Kolom wajib tidak ditemukan pada file Excel.');
            }
        }

        if (! isset($headerMap['recommendation_flat']) && count($recommendationColumns) === 0) {
            $this->addError($errors, $bestRowNumber, 'REKOMENDASI TAT', 'Kolom rekomendasi TAT tidak ditemukan.');
        }

        if ($duplicateHeaders) {
            $this->addError($errors, $bestRowNumber, 'HEADER', 'Kolom ganda: '.implode(', ', array_unique($duplicateHeaders)).'.');
        }

        $headerMap['recommendation_columns'] = $recommendationColumns;

        return [$headerMap, $bestRowNumber + ($hasSubHeaders ? 1 : 0), $errors];
    }

    private function resolveHeader(mixed $value): ?string
    {
        $header = $this->normalizeControlledValue($value);

        if ($header === '') {
            return null;
        }

        $aliases = [
            'NO' => 'no',
            'NOMOR' => 'no',
            'NO/BLN' => 'no_bln',
            'NO BLN' => 'no_bln',
            'ASAL PENGAJUAN' => 'asal_pengajuan',
            'TANGGAL SURAT' => 'tanggal_surat',
            'TGL SURAT' => 'tanggal_surat',
            'TANGGAL BERKAS DITERIMA' => 'tanggal_berkas_diterima',
            'TGL BERKAS DITERIMA' => 'tanggal_berkas_diterima',
            'TANGGAL PELAKSANAAN' => 'tanggal_pelaksanaan',
            'TGL PELAKSANAAN' => 'tanggal_pelaksanaan',
            'NO SURAT PENGAJUAN' => 'no_surat_pengajuan',
            'NOMOR SURAT PENGAJUAN' => 'no_surat_pengajuan',
            'NO LKN/LP/LI' => 'no_lkn_lp_li',
            'NO LKN LP LI' => 'no_lkn_lp_li',
            'PASAL YANG DISANGKAKAN' => 'pasal_yang_disangkakan',
            'TGL PENANGKAPAN' => 'tanggal_penangkapan',
            'TANGGAL PENANGKAPAN' => 'tanggal_penangkapan',
            'NAMA' => 'nama',
            'NO REGISTER' => 'no_register',
            'NOMOR REGISTER' => 'no_register',
            'ALAMAT KTP' => 'alamat_ktp',
            'ALAMAT DOMISILI' => 'alamat_domisili',
            'TEMPAT LAHIR' => 'tempat_lahir',
            'TANGGAL LAHIR' => 'tanggal_lahir',
            'TGL LAHIR' => 'tanggal_lahir',
            'JENIS KELAMIN' => 'jenis_kelamin',
            'USIA' => 'usia',
            'PENDIDIKAN' => 'pendidikan',
            'PEKERJAAN' => 'pekerjaan',
            'NO HANDPHONE' => 'no_handphone',
            'NO HP' => 'no_handphone',
            'NOMOR HP' => 'no_handphone',
            'NIK' => 'nik',
            'JENIS NARKOTIKA' => 'jenis_narkotika',
            'JENIS NARKO' => 'jenis_narkotika',
            'JUMLAH / BERAT' => 'jumlah',
            'JUMLAH/BERAT' => 'jumlah',
            'JUMLAH' => 'jumlah',
            'BERAT' => 'jumlah',
            'SATUAN' => 'satuan',
            'HASIL ASESMEN HUKUM' => 'hasil_asesmen_hukum',
            'HASIL ASESMEN MEDIS' => 'hasil_asesmen_medis',
            'PELAKSANAAN REKOMENDASI' => 'pelaksanaan_rekomendasi',
            'PELAKSANAAN REKOM' => 'pelaksanaan_rekomendasi',
            'HASIL REKOM TAT' => 'hasil_rekom_tat',
            'KET' => 'keterangan',
            'KETERANGAN' => 'keterangan',
            'REKOMENDASI TAT' => 'recommendation_flat',
        ];

        if (isset($aliases[$header])) {
            return $aliases[$header];
        }

        $recommendation = preg_replace('/^REKOMENDASI TAT\s*[:\-]?\s*/', '', $header);

        if (isset(self::RECOMMENDATION_COLUMNS[$recommendation])) {
            return 'recommendation:'.self::RECOMMENDATION_COLUMNS[$recommendation]->value;
        }

        if (isset(self::RECOMMENDATION_COLUMNS[$header])) {
            return 'recommendation:'.self::RECOMMENDATION_COLUMNS[$header]->value;
        }

        return null;
    }

    private function hasRecommendationSubHeaders(array $row): bool
    {
        $matches = 0;

        foreach ($row as $value) {
            if (isset(self::RECOMMENDATION_COLUMNS[$this->normalizeControlledValue($value)])) {
                $matches++;
            }
        }

        return $matches >= 2;
    }

    /**
     * @param  array<int, scalar|null>  $rawRow
     * @param  array<string, mixed>  $headerMap
     * @param  array<int, array{row: int, field: string, message: string}>  $errors
     * @return array<string, mixed>
     */
    private function normalizeRow(array $rawRow, int $rowNumber, array $headerMap, array &$errors, Bnnk $targetBnnk): array
    {
        $record = [
            'status' => TatRegisterStatus::Final,
            'bnnk_id' => $targetBnnk->id,
        ];

        $record['no'] = $this->integerField($this->cell($rawRow, $headerMap, 'no'), $rowNumber, 'NO', $errors);
        $record['no_bln'] = $this->integerField($this->cell($rawRow, $headerMap, 'no_bln'), $rowNumber, 'NO/BLN', $errors);

        foreach ([
            'asal_pengajuan' => 'ASAL PENGAJUAN',
            'no_surat_pengajuan' => 'NO SURAT PENGAJUAN',
            'no_lkn_lp_li' => 'NO LKN/LP/LI',
            'pasal_yang_disangkakan' => 'PASAL YANG DISANGKAKAN',
            'nama' => 'NAMA',
            'no_register' => 'NO REGISTER',
            'alamat_ktp' => 'ALAMAT KTP',
            'alamat_domisili' => 'ALAMAT DOMISILI',
            'tempat_lahir' => 'TEMPAT LAHIR',
            'pendidikan' => 'PENDIDIKAN',
            'pekerjaan' => 'PEKERJAAN',
            'no_handphone' => 'NO HANDPHONE',
            'hasil_asesmen_hukum' => 'HASIL ASESMEN HUKUM',
            'hasil_asesmen_medis' => 'HASIL ASESMEN MEDIS',
            'hasil_rekom_tat' => 'HASIL REKOM TAT',
        ] as $field => $label) {
            $record[$field] = $this->textField($this->cell($rawRow, $headerMap, $field), $rowNumber, $label, $errors, $field === 'no_register' ? 255 : null);
        }

        foreach ([
            'tanggal_surat' => 'TANGGAL SURAT',
            'tanggal_berkas_diterima' => 'TANGGAL BERKAS DITERIMA',
            'tanggal_pelaksanaan' => 'TANGGAL PELAKSANAAN',
            'tanggal_penangkapan' => 'TGL PENANGKAPAN',
            'tanggal_lahir' => 'TANGGAL LAHIR',
        ] as $field => $label) {
            $record[$field] = $this->dateField($this->cell($rawRow, $headerMap, $field), $rowNumber, $label, $errors);
        }

        $record['jenis_kelamin'] = $this->genderField($this->cell($rawRow, $headerMap, 'jenis_kelamin'), $rowNumber, $errors);
        $record['usia'] = $this->integerField($this->cell($rawRow, $headerMap, 'usia'), $rowNumber, 'USIA', $errors, 0, 150);
        $record['no_handphone'] = $this->optionalTextField($record['no_handphone'], $rowNumber, 'NO HANDPHONE', $errors, 50);
        $record['nik'] = $this->optionalTextField($this->cell($rawRow, $headerMap, 'nik'), $rowNumber, 'NIK', $errors, 32);
        $record['keterangan'] = $this->optionalTextField($this->cell($rawRow, $headerMap, 'keterangan'), $rowNumber, 'KET', $errors);
        $record['pelaksanaan_rekomendasi'] = $this->executionField($this->cell($rawRow, $headerMap, 'pelaksanaan_rekomendasi'), $rowNumber, $errors);
        $record['rekomendasi_tat'] = $this->recommendationField($rawRow, $rowNumber, $headerMap, $errors);

        [$record['no_bln_month'], $record['no_bln_year']] = array_values(
            $this->tatNumberService->contextFromTanggalPelaksanaan($record['tanggal_pelaksanaan'])
        );

        return [
            'excel_row' => $rowNumber,
            'register' => $record,
            'narcotics' => $this->narcoticField($rawRow, $rowNumber, $headerMap, $errors),
        ];
    }

    /**
     * @param  array<int, scalar|null>  $rawRow
     * @param  array<string, mixed>  $headerMap
     * @return array<int, array<string, mixed>>
     */
    private function narcoticField(array $rawRow, int $rowNumber, array $headerMap, array &$errors): array
    {
        $typeName = $this->optionalTextField($this->cell($rawRow, $headerMap, 'jenis_narkotika'), $rowNumber, 'JENIS NARKOTIKA', $errors);
        $quantity = $this->optionalDecimalField($this->cell($rawRow, $headerMap, 'jumlah'), $rowNumber, 'JUMLAH / BERAT', $errors);
        $unitName = $this->optionalTextField($this->cell($rawRow, $headerMap, 'satuan'), $rowNumber, 'SATUAN', $errors);

        if (! $typeName) {
            if ($quantity !== null || $unitName) {
                $this->addError($errors, $rowNumber, 'JENIS NARKOTIKA', 'Jenis narkotika wajib diisi jika jumlah atau satuan diisi.');
            }

            return [];
        }

        $type = NarcoticType::query()->where('is_active', true)->get()
            ->first(fn (NarcoticType $item) => $this->normalizeControlledValue($item->name) === $this->normalizeControlledValue($typeName));

        if (! $type) {
            $this->addError($errors, $rowNumber, 'JENIS NARKOTIKA', 'Jenis narkotika "'.$typeName.'" tidak terdaftar pada master data.');
        }

        $unit = null;

        if ($unitName) {
            $unit = Unit::query()->where('is_active', true)->get()
                ->first(fn (Unit $item) => $this->normalizeControlledValue($item->name) === $this->normalizeControlledValue($unitName));

            if (! $unit) {
                $this->addError($errors, $rowNumber, 'SATUAN', 'Satuan "'.$unitName.'" tidak terdaftar pada master data.');
            }
        }

        if (($quantity === null) !== ($unitName === null || $unitName === '')) {
            $this->addError($errors, $rowNumber, 'JUMLAH / BERAT', 'Jumlah dan satuan harus sama-sama diisi atau sama-sama kosong.');
        }

        return $type ? [[
            'narcotic_type_id' => $type->id,
            'type_name' => $type->name,
            'jumlah' => $quantity,
            'unit_id' => $unit?->id,
            'unit_name' => $unit?->name,
        ]] : [];
    }

    /**
     * @param  array<int, scalar|null>  $rawRow
     * @param  array<string, mixed>  $headerMap
     */
    private function recommendationField(array $rawRow, int $rowNumber, array $headerMap, array &$errors): ?string
    {
        if (isset($headerMap['recommendation_flat'])) {
            $value = $this->optionalTextField($this->cell($rawRow, $headerMap, 'recommendation_flat'), $rowNumber, 'REKOMENDASI TAT', $errors);
            $recommendation = $this->mapRecommendation($value);

            if (! $recommendation) {
                $this->addError($errors, $rowNumber, 'REKOMENDASI TAT', 'Rekomendasi TAT tidak dikenal atau belum diisi.');
            }

            return $recommendation?->value;
        }

        $selected = [];
        $unknownMarker = false;

        foreach ($headerMap['recommendation_columns'] as $recommendationValue => $column) {
            $marker = $rawRow[$column] ?? null;

            if ($this->isBlank($marker)) {
                continue;
            }

            if ($this->isSelectedMarker($marker)) {
                $selected[] = $recommendationValue;

                continue;
            }

            if ($this->isUnselectedMarker($marker)) {
                continue;
            }

            $unknownMarker = true;
            $this->addError($errors, $rowNumber, 'REKOMENDASI TAT', 'Penanda pilihan rekomendasi tidak dikenal.');
        }

        if ($unknownMarker) {
            return null;
        }

        if (count($selected) === 0) {
            $this->addError($errors, $rowNumber, 'REKOMENDASI TAT', 'Rekomendasi TAT wajib memilih tepat satu pilihan.');
        } elseif (count($selected) > 1) {
            $this->addError($errors, $rowNumber, 'REKOMENDASI TAT', 'Rekomendasi TAT memiliki lebih dari satu pilihan.');
        }

        return count($selected) === 1 ? $selected[0] : null;
    }

    private function mapRecommendation(?string $value): ?TatRecommendation
    {
        if (! $value) {
            return null;
        }

        return match ($this->normalizeControlledValue($value)) {
            'RAWAT JALAN' => TatRecommendation::RawatJalan,
            'RAWAT INAP' => TatRecommendation::RawatInap,
            'REHAB DI LAPAS/RUTAN', 'REHAB LAPAS/RUTAN' => TatRecommendation::RehabLapasRutan,
            'TIDAK REHAB', 'TIDAK REHAB / PROSES HUKUM', 'TIDAK REHAB/PROSES HUKUM', 'TIDAK REHAB (PROSES HUKUM)' => TatRecommendation::TidakRehab,
            default => null,
        };
    }

    private function executionField(mixed $value, int $rowNumber, array &$errors): ?string
    {
        $value = $this->optionalTextField($value, $rowNumber, 'PELAKSANAAN REKOMENDASI', $errors);
        $normalized = $value ? $this->normalizeControlledValue($value) : null;

        return match ($normalized) {
            'DILAKSANAKAN' => RecommendationExecution::Dilaksanakan->value,
            'TIDAK DILAKSANAKAN' => RecommendationExecution::TidakDilaksanakan->value,
            default => $this->invalidControlledField($errors, $rowNumber, 'PELAKSANAAN REKOMENDASI', 'Nilai pelaksanaan rekomendasi tidak dikenal.'),
        };
    }

    private function genderField(mixed $value, int $rowNumber, array &$errors): ?string
    {
        $value = $this->optionalTextField($value, $rowNumber, 'JENIS KELAMIN', $errors);
        $normalized = $value ? $this->normalizeControlledValue($value) : null;

        return match ($normalized) {
            'L', 'LAKI-LAKI', 'LAKI LAKI' => Gender::L->value,
            'P', 'PEREMPUAN' => Gender::P->value,
            default => $this->invalidControlledField($errors, $rowNumber, 'JENIS KELAMIN', 'Jenis kelamin harus L atau P.'),
        };
    }

    private function invalidControlledField(array &$errors, int $rowNumber, string $field, string $message): ?string
    {
        $this->addError($errors, $rowNumber, $field, $message);

        return null;
    }

    private function textField(mixed $value, int $rowNumber, string $field, array &$errors, ?int $maxLength = null): ?string
    {
        $value = $this->optionalTextField($value, $rowNumber, $field, $errors, $maxLength);

        if ($value === null) {
            $this->addError($errors, $rowNumber, $field, 'Kolom wajib diisi.');
        }

        return $value;
    }

    private function optionalTextField(mixed $value, int $rowNumber, string $field, array &$errors, ?int $maxLength = null): ?string
    {
        if ($this->isBlank($value)) {
            return null;
        }

        $value = trim($this->stringValue($value));

        if ($maxLength && mb_strlen($value) > $maxLength) {
            $this->addError($errors, $rowNumber, $field, 'Nilai melebihi batas '.$maxLength.' karakter.');
        }

        return $value;
    }

    private function integerField(mixed $value, int $rowNumber, string $field, array &$errors, int $minimum = 1, int $maximum = 2147483647): ?int
    {
        if ($this->isBlank($value)) {
            $this->addError($errors, $rowNumber, $field, 'Kolom wajib diisi.');

            return null;
        }

        $value = trim($this->stringValue($value));

        if (! preg_match('/^\d+(?:\.0+)?$/', $value)) {
            $this->addError($errors, $rowNumber, $field, 'Nilai harus berupa bilangan bulat.');

            return null;
        }

        $integer = (int) $value;

        if ($integer < $minimum || $integer > $maximum) {
            $this->addError($errors, $rowNumber, $field, 'Nilai berada di luar batas yang diperbolehkan.');
        }

        return $integer;
    }

    private function dateField(mixed $value, int $rowNumber, string $field, array &$errors): ?string
    {
        if ($this->isBlank($value)) {
            $this->addError($errors, $rowNumber, $field, 'Kolom wajib diisi.');

            return null;
        }

        $date = $this->parseDate($value);

        if (! $date) {
            $this->addError($errors, $rowNumber, $field, 'Format tanggal tidak valid.');
        }

        return $date;
    }

    private function optionalDecimalField(mixed $value, int $rowNumber, string $field, array &$errors): ?string
    {
        if ($this->isBlank($value)) {
            return null;
        }

        $value = str_replace(' ', '', $this->stringValue($value));

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            $this->addError($errors, $rowNumber, $field, 'Jumlah harus berupa angka, termasuk angka desimal.');

            return null;
        }

        if ((float) $value < 0) {
            $this->addError($errors, $rowNumber, $field, 'Jumlah tidak boleh negatif.');
        }

        return $value;
    }

    private function parseDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial < 1 || $serial > 2958465) {
                return null;
            }

            return Carbon::create(1899, 12, 30)->addDays((int) floor($serial))->format('Y-m-d');
        }

        $value = trim($this->stringValue($value));

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function isSelectedMarker(mixed $value): bool
    {
        return in_array($this->normalizeControlledValue($value), ['1', 'X', 'V', 'YA', 'YES', 'TRUE', 'DIPILIH', '✓', '✔'], true);
    }

    private function isUnselectedMarker(mixed $value): bool
    {
        return in_array($this->normalizeControlledValue($value), ['0', '-', 'TIDAK', 'NO', 'FALSE'], true);
    }

    private function cell(array $row, array $headerMap, string $field): mixed
    {
        return isset($headerMap[$field]) ? ($row[$headerMap[$field]] ?? null) : null;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (! $this->isBlank($value)) {
                return false;
            }
        }

        return true;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function stringValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    private function normalizeControlledValue(mixed $value): string
    {
        $value = trim($this->stringValue($value));
        $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{row: int, field: string, message: string}>  $errors
     */
    private function validateDuplicatesInsideFile(array $rows, array &$errors): void
    {
        $registers = [];
        $registerLabels = [];
        $scopedNumbers = [];

        foreach ($rows as $row) {
            $excelRow = $row['excel_row'];
            $register = $row['register'];
            $registerKey = $this->duplicateKey($register['no_register'] ?? null);

            if ($registerKey) {
                $registers[$registerKey][] = $excelRow;
                $registerLabels[$registerKey] ??= $register['no_register'];
            }

            if ($register['no_bln'] !== null && $register['no_bln_month'] !== null && $register['no_bln_year'] !== null) {
                $scopeKey = implode(':', [$register['bnnk_id'], $register['no_bln_month'], $register['no_bln_year'], $register['no_bln']]);
                $scopedNumbers[$scopeKey][] = $excelRow;
            }
        }

        foreach ($registers as $value => $rowNumbers) {
            if (count($rowNumbers) < 2) {
                continue;
            }

            foreach ($rowNumbers as $rowNumber) {
                $others = array_values(array_diff($rowNumbers, [$rowNumber]));
                $this->addError($errors, $rowNumber, 'NO REGISTER', 'NO REGISTER "'.$registerLabels[$value].'" duplikat di file yang sama dengan baris '.implode(', ', $others).'.');
            }
        }

        foreach ($scopedNumbers as $rowNumbers) {
            if (count($rowNumbers) < 2) {
                continue;
            }

            foreach ($rowNumbers as $rowNumber) {
                $others = array_values(array_diff($rowNumbers, [$rowNumber]));
                $this->addError($errors, $rowNumber, 'NO/BLN', 'NO/BLN duplikat dalam BNNK, bulan, dan tahun yang sama dengan baris '.implode(', ', $others).'.');
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{row: int, field: string, message: string}>  $errors
     */
    private function validateDatabaseDuplicates(array $rows, array &$errors): void
    {
        $registerKeys = [];
        $scopedKeys = [];

        foreach ($rows as $row) {
            $register = $row['register'];
            $registerKey = $this->duplicateKey($register['no_register'] ?? null);

            if ($registerKey) {
                $registerKeys[$registerKey][] = $row['excel_row'];
            }

            if ($register['no_bln'] !== null && $register['no_bln_month'] !== null && $register['no_bln_year'] !== null) {
                $key = implode(':', [$register['bnnk_id'], $register['no_bln_month'], $register['no_bln_year'], $register['no_bln']]);
                $scopedKeys[$key][] = $row['excel_row'];
            }
        }

        if ($registerKeys) {
            $registerValues = collect($rows)
                ->map(fn (array $row) => $row['register']['no_register'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $existingRegisters = TatRegister::withTrashed()
                ->where(function ($query) use ($registerValues): void {
                    foreach ($registerValues as $value) {
                        $query->orWhereRaw('LOWER(no_register) = ?', [mb_strtolower($value, 'UTF-8')]);
                    }
                })
                ->pluck('no_register')
                ->all();

            $existingRegisterKeys = collect($existingRegisters)
                ->mapWithKeys(fn ($value) => [$this->duplicateKey($value) => $value])
                ->filter(fn ($value, $key) => $key !== null)
                ->all();

            foreach ($existingRegisterKeys as $existingRegisterKey => $existingRegister) {
                foreach ($registerKeys[$existingRegisterKey] ?? [] as $rowNumber) {
                    $this->addError($errors, $rowNumber, 'NO REGISTER', 'NO REGISTER "'.$existingRegister.'" sudah ada di database.');
                }
            }
        }

        if (! $scopedKeys) {
            return;
        }

        $targetBnnkId = $rows[0]['register']['bnnk_id'] ?? 0;
        $existing = TatRegister::withTrashed()
            ->where('bnnk_id', $targetBnnkId)
            ->where(function ($query) use ($rows): void {
                foreach ($rows as $row) {
                    $register = $row['register'];
                    $query->orWhere(function ($nested) use ($register): void {
                        $nested->where('no_bln', $register['no_bln'])
                            ->where('no_bln_month', $register['no_bln_month'])
                            ->where('no_bln_year', $register['no_bln_year']);
                    });
                }
            })
            ->get(['no_bln', 'no_bln_month', 'no_bln_year', 'bnnk_id']);

        foreach ($existing as $record) {
            $key = implode(':', [$record->bnnk_id, $record->no_bln_month, $record->no_bln_year, $record->no_bln]);

            foreach ($scopedKeys[$key] ?? [] as $rowNumber) {
                $this->addError($errors, $rowNumber, 'NO/BLN', 'NO/BLN sudah digunakan pada BNNK, bulan, dan tahun tersebut.');
            }
        }
    }

    private function duplicateKey(mixed $value): ?string
    {
        return $this->isBlank($value) ? null : mb_strtolower(trim($this->stringValue($value)), 'UTF-8');
    }

    /**
     * @param  array<int, array{row: int, field: string, message: string}>  $errors
     */
    private function addError(array &$errors, int $row, string $field, string $message): void
    {
        $errors[] = compact('row', 'field', 'message');
    }

    private function payloadPath(string $token): string
    {
        return 'tat-imports/'.$token.'.payload';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readPayload(User $user, string $token): ?array
    {
        if (! preg_match('/^[A-Za-z0-9]+$/', $token)) {
            return null;
        }

        $path = $this->payloadPath($token);

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString(Storage::disk('local')->get($path)), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            Storage::disk('local')->delete($path);

            return null;
        }

        if (($payload['user_id'] ?? null) !== $user->id) {
            return null;
        }

        if (($payload['expires_at'] ?? 0) < now()->timestamp) {
            Storage::disk('local')->delete($path);

            return null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writePayload(string $token, array $payload): void
    {
        Storage::disk('local')->put($this->payloadPath($token), Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)));
    }
}
