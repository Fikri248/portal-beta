<?php

namespace App\Http\Controllers;

use App\Models\TatRegister;
use App\Services\TatRegisterPhotoService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TatRegisterPhotoController extends Controller
{
    public function __construct(private readonly TatRegisterPhotoService $photoService) {}

    public function __invoke(TatRegister $tatRegister): BinaryFileResponse
    {
        Gate::authorize('view', $tatRegister);

        $photoPath = $tatRegister->foto_klien;

        abort_unless($this->photoService->exists($photoPath), 404);

        $extension = pathinfo($photoPath, PATHINFO_EXTENSION) ?: 'jpg';

        return response()->file($this->photoService->absolutePath($photoPath), [
            'Content-Type' => $this->photoService->mimeType($photoPath),
            'Content-Disposition' => 'inline; filename="client-photo.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
