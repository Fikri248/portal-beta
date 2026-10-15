<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadTatRegisterImportRequest;
use App\Models\Bnnk;
use App\Models\TatRegister;
use App\Services\TatRegisterImportException;
use App\Services\TatRegisterImportService;
use App\Services\TatRegisterExcelTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class TatRegisterImportController extends Controller
{
    public function __construct(
        private readonly TatRegisterImportService $service,
        private readonly TatRegisterExcelTemplateService $templateService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->isBnnk(), 403);
        Gate::authorize('create', TatRegister::class);

        $bnnks = Bnnk::whereKey($request->user()->bnnk_id)->get();

        return view('tat-registers.import.index', compact('bnnks'));
    }

    public function downloadTemplate(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($request->user()->isBnnk(), 403);
        Gate::authorize('create', TatRegister::class);

        $content = $this->templateService->generate();

        return response($content, 200, [
            'Content-Type' => TatRegisterExcelTemplateService::CONTENT_TYPE,
            'Content-Disposition' => 'attachment; filename="'.TatRegisterExcelTemplateService::FILENAME.'"',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    public function validateFile(UploadTatRegisterImportRequest $request): RedirectResponse
    {
        abort_unless($request->user()->isBnnk(), 403);

        $validated = $request->validated();

        try {
            $targetBnnk = $this->service->resolveTargetBnnk($request->user(), $validated['bnnk_id'] ?? null);
        } catch (TatRegisterImportException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $result = $this->service->validateFile($validated['file'], $targetBnnk);

        if (! $result['valid']) {
            $affectedRows = collect($result['errors'])->pluck('row')->unique()->count();

            return back()
                ->withInput()
                ->with('import_errors', $result['errors'])
                ->with('import_error_summary', [
                    'total' => count($result['errors']),
                    'rows' => $affectedRows,
                ]);
        }

        $token = $this->service->storePreview($request->user(), $targetBnnk, $result['rows']);

        return redirect()->route('tat-registers.import.preview', $token);
    }

    public function preview(Request $request, string $token): View|RedirectResponse
    {
        abort_unless($request->user()->isBnnk(), 403);
        Gate::authorize('create', TatRegister::class);

        try {
            $payload = $this->service->getPreview($request->user(), $token);
        } catch (TatRegisterImportException $exception) {
            return redirect()->route('tat-registers.import.index')->with('error', $exception->getMessage());
        }

        return view('tat-registers.import.preview', compact('payload', 'token'));
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        abort_unless($request->user()->isBnnk(), 403);
        Gate::authorize('create', TatRegister::class);

        try {
            $count = $this->service->confirm($request->user(), $token);
        } catch (TatRegisterImportException $exception) {
            return redirect()->route('tat-registers.import.index')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('tat-registers.import.index')->with('error', 'Import gagal diproses. Tidak ada data yang disimpan.');
        }

        return redirect()->route('tat-registers.index')->with('success', $count.' data Register TAT berhasil diimpor.');
    }

    public function cancel(Request $request, string $token): RedirectResponse
    {
        abort_unless($request->user()->isBnnk(), 403);
        Gate::authorize('create', TatRegister::class);
        $this->service->discardPreview($request->user(), $token);

        return redirect()->route('tat-registers.import.index')->with('success', 'Preview import dibatalkan.');
    }
}
