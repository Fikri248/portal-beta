<?php

namespace App\Http\Controllers;

use App\Enums\AuditLogAction;
use App\Enums\TatRegisterStatus;
use App\Http\Requests\TatRegisterRequest;
use App\Models\Bnnk;
use App\Models\NarcoticType;
use App\Models\TatRegister;
use App\Models\Unit;
use App\Services\AuditLogService;
use App\Services\TatNumberService;
use App\Services\TatRegisterPhotoService;
use App\Support\Pagination;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TatRegisterController extends Controller
{
    public function __construct(
        private readonly TatNumberService $tatNumberService,
        private readonly TatRegisterPhotoService $photoService,
        private readonly AuditLogService $auditLogService,
    ) {}

    private function scoped(Request $request, bool $trashed = false)
    {
        $query = $trashed ? TatRegister::withTrashed() : TatRegister::query();

        return $request->user()->isBnnp() ? $query : $query->where('bnnk_id', $request->user()->bnnk_id);
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TatRegister::class);
        $perPage = Pagination::resolve($request);
        $records = $this->scoped($request)->where('status', TatRegisterStatus::Final)->with('bnnk')->latest()->paginate($perPage)->withQueryString();

        return view('tat-registers.index', compact('records', 'perPage'));
    }

    public function drafts(Request $request): View
    {
        Gate::authorize('viewAny', TatRegister::class);
        $perPage = Pagination::resolve($request);
        $records = $this->scoped($request)->where('status', TatRegisterStatus::Draft)->with('bnnk')->latest()->paginate($perPage)->withQueryString();

        return view('tat-registers.index', ['records' => $records, 'perPage' => $perPage, 'pageTitle' => 'Draft Register TAT', 'pageDescription' => 'Data yang masih dapat dilengkapi sebelum difinalisasi.']);
    }

    public function archive(Request $request): View
    {
        Gate::authorize('viewAny', TatRegister::class);
        $perPage = Pagination::resolve($request);
        $records = $this->scoped($request, true)->whereNotNull('deleted_at')->with('bnnk')->latest('deleted_at')->paginate($perPage)->withQueryString();

        return view('tat-registers.archive', compact('records', 'perPage'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', TatRegister::class);

        return view('tat-registers.form', array_merge(['tatRegister' => new TatRegister, 'photoAvailable' => false], $this->formData($request)));
    }

    public function store(TatRegisterRequest $request): RedirectResponse
    {
        $record = $this->saveRecord($request, new TatRegister);

        return redirect()->route('tat-registers.show', $record)->with('success', 'Register TAT berhasil disimpan.');
    }

    public function show(Request $request, TatRegister $tatRegister): View
    {
        Gate::authorize('view', $tatRegister);
        $tatRegister->load(['bnnk', 'narcotics.narcoticType', 'narcotics.unit']);

        return view('tat-registers.show', ['tatRegister' => $tatRegister, 'photoAvailable' => $this->photoService->exists($tatRegister->foto_klien)]);
    }

    public function edit(Request $request, TatRegister $tatRegister): View
    {
        Gate::authorize('update', $tatRegister);
        $tatRegister->load('narcotics');

        return view('tat-registers.form', array_merge(['tatRegister' => $tatRegister, 'photoAvailable' => $this->photoService->exists($tatRegister->foto_klien)], $this->formData($request)));
    }

    public function update(TatRegisterRequest $request, TatRegister $tatRegister): RedirectResponse
    {
        $record = $this->saveRecord($request, $tatRegister);

        return redirect()->route('tat-registers.show', $record)->with('success', 'Register TAT berhasil diperbarui.');
    }

    public function destroy(Request $request, TatRegister $tatRegister): RedirectResponse
    {
        Gate::authorize('delete', $tatRegister);

        DB::transaction(function () use ($request, $tatRegister): void {
            $tatRegister->delete();
            $this->auditLogService->record(
                AuditLogAction::TatArchived,
                entity: $tatRegister,
                bnnkId: $tatRegister->bnnk_id,
                metadata: [
                    'no_register' => $tatRegister->no_register,
                ],
                actor: $request->user(),
            );
        });

        return redirect()->route('tat-registers.index')->with('success', 'Register TAT dipindahkan ke arsip.');
    }

    public function restore(Request $request, int $tatRegisterId): RedirectResponse
    {
        $record = TatRegister::onlyTrashed()->findOrFail($tatRegisterId);
        Gate::authorize('restore', $record);

        try {
            DB::transaction(function () use ($request, $record): void {
                $record->restore();
                $this->auditLogService->record(
                    AuditLogAction::TatRestored,
                    entity: $record,
                    bnnkId: $record->bnnk_id,
                    metadata: [
                        'no_register' => $record->no_register,
                    ],
                    actor: $request->user(),
                );
            });
        } catch (Throwable $exception) {
            if ($this->isNoBlnConstraintConflict($exception)) {
                return redirect()->route('tat-registers.archive')->with('error', 'NO/BLN tersebut tidak dapat dipulihkan karena sudah digunakan pada BNNK, bulan, dan tahun yang sama.');
            }

            throw $exception;
        }

        return redirect()->route('tat-registers.archive')->with('success', 'Register TAT berhasil dipulihkan.');
    }

    private function formData(Request $request): array
    {
        return ['bnnks' => $request->user()->isBnnp() ? Bnnk::orderBy('name')->get() : collect(), 'narcoticTypes' => NarcoticType::where('is_active', true)->orderBy('name')->get(), 'units' => Unit::where('is_active', true)->orderBy('name')->get()];
    }

    private function saveRecord(TatRegisterRequest $request, TatRegister $record): TatRegister
    {
        $validated = $request->validated();
        $user = $request->user();
        $status = TatRegisterStatus::from($validated['status']);
        $ownership = $user->isBnnp() ? (int) ($validated['bnnk_id'] ?? $record->bnnk_id ?? $user->bnnk_id) : (int) $user->bnnk_id;
        if (! $ownership) {
            abort(422, 'BNNK wajib dipilih.');
        }
        $hasNarcotics = array_key_exists('narcotics', $validated);
        $narcotics = $validated['narcotics'] ?? [];
        $photo = $validated['foto_klien'] ?? null;
        $oldPhotoPath = $record->exists ? $record->foto_klien : null;
        $oldStatus = $record->exists ? $record->status : null;
        $oldState = $record->exists ? $this->auditLogService->tatSnapshot($record) : null;
        $wasExisting = $record->exists;
        $newPhotoPath = null;
        unset($validated['narcotics'], $validated['bnnk_id'], $validated['status'], $validated['no_bln'], $validated['no_bln_month'], $validated['no_bln_year'], $validated['foto_klien']);
        $data = $validated;
        $data['status'] = $status;
        $data['bnnk_id'] = $ownership;
        $numberContext = $this->tatNumberService->contextFromTanggalPelaksanaan($data['tanggal_pelaksanaan'] ?? null);
        $data = array_merge($data, $numberContext);
        $canKeepNumber = $wasExisting
            && $record->no_bln !== null
            && (int) $record->bnnk_id === $ownership
            && (int) $record->no_bln_month === (int) ($numberContext['no_bln_month'] ?? 0)
            && (int) $record->no_bln_year === (int) ($numberContext['no_bln_year'] ?? 0);
        $workingRecord = $record;
        try {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    DB::transaction(function () use (&$workingRecord, &$data, $numberContext, $canKeepNumber, $narcotics, $hasNarcotics, $photo, &$newPhotoPath, $wasExisting, $oldStatus, $oldState, $status, $ownership, $request, $oldPhotoPath): void {
                        if ($numberContext['no_bln_month'] === null || $numberContext['no_bln_year'] === null) {
                            $data['no_bln'] = null;
                        } elseif ($canKeepNumber) {
                            $data['no_bln'] = $workingRecord->no_bln;
                        } else {
                            $data['no_bln'] = $this->tatNumberService->nextNumber(
                                $ownership,
                                $data['tanggal_pelaksanaan'] ?? null,
                                $workingRecord->exists ? $workingRecord->id : null,
                            );
                        }

                        if ($data['no_bln'] !== null && $this->tatNumberService->isOccupied(
                            $ownership,
                            (int) $numberContext['no_bln_month'],
                            (int) $numberContext['no_bln_year'],
                            (int) $data['no_bln'],
                            $workingRecord->exists ? $workingRecord->id : null,
                        )) {
                            throw ValidationException::withMessages([
                                'no_bln' => 'NO/BLN tersebut sudah digunakan pada BNNK, bulan, dan tahun yang sama. Silakan muat ulang data.',
                            ]);
                        }

                        $workingRecord->forceFill($data)->save();
                        if ($photo instanceof UploadedFile) {
                            $newPhotoPath = $this->photoService->store($workingRecord, $photo);
                            $workingRecord->forceFill(['foto_klien' => $newPhotoPath])->save();
                        }
                        if ($hasNarcotics) {
                            $workingRecord->narcotics()->delete();
                            foreach ($narcotics as $narcotic) {
                                $workingRecord->narcotics()->create($narcotic);
                            }
                        }

                        $savedRecord = $workingRecord->fresh();

                        if (! $wasExisting) {
                            $action = $status === TatRegisterStatus::Draft
                                ? AuditLogAction::TatCreatedDraft
                                : AuditLogAction::TatCreatedFinal;

                            $this->auditLogService->record(
                                $action,
                                entity: $savedRecord,
                                bnnkId: $ownership,
                                newValues: [
                                    'no_register' => $savedRecord->no_register,
                                    'status' => $savedRecord->status,
                                ],
                                metadata: [
                                    'no_bln' => $savedRecord->no_bln,
                                    'created_at' => $savedRecord->created_at,
                                ],
                                actor: $request->user(),
                            );
                        } else {
                            $newState = $this->auditLogService->tatSnapshot($savedRecord);
                            $changes = $this->auditLogService->changedValues($oldState ?? [], $newState);
                            $action = $oldStatus === TatRegisterStatus::Draft && $status === TatRegisterStatus::Final
                                ? AuditLogAction::TatFinalized
                                : ($status === TatRegisterStatus::Draft ? AuditLogAction::TatUpdatedDraft : AuditLogAction::TatUpdatedFinal);

                            $this->auditLogService->record(
                                $action,
                                entity: $savedRecord,
                                bnnkId: $ownership,
                                oldValues: $changes['old'],
                                newValues: $changes['new'],
                                actor: $request->user(),
                            );
                        }

                        if ($photo instanceof UploadedFile) {
                            $this->auditLogService->record(
                                $oldPhotoPath ? AuditLogAction::TatPhotoReplaced : AuditLogAction::TatPhotoUploaded,
                                entity: $savedRecord,
                                bnnkId: $ownership,
                                metadata: ['change' => $oldPhotoPath ? 'replaced' : 'added'],
                                actor: $request->user(),
                            );
                        }
                    });

                    break;
                } catch (Throwable $exception) {
                    if (! $this->isNoBlnConstraintConflict($exception) || $attempt === 3) {
                        if ($this->isNoBlnConstraintConflict($exception)) {
                            throw ValidationException::withMessages([
                                'no_bln' => 'NO/BLN tersebut sudah digunakan pada BNNK, bulan, dan tahun yang sama. Silakan muat ulang data.',
                            ]);
                        }

                        throw $exception;
                    }
                    if (! $wasExisting) {
                        $workingRecord = new TatRegister;
                    }
                }
            }
        } catch (Throwable $exception) {
            $this->photoService->delete($newPhotoPath);
            throw $exception;
        }
        if ($newPhotoPath && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            $this->photoService->delete($oldPhotoPath);
        }

        return $workingRecord->fresh();
    }

    private function isNoBlnConstraintConflict(Throwable $exception): bool
    {
        do {
            if ($exception instanceof QueryException) {
                $message = strtolower($exception->getMessage());

                if (str_contains($message, 'tat_registers_no_bln_scope_unique')
                    || (str_contains($message, 'no_bln_month')
                        && str_contains($message, 'no_bln_year')
                        && str_contains($message, 'no_bln')
                        && str_contains($message, 'bnnk_id'))) {
                    return true;
                }
            }

            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);

        return false;
    }
}
