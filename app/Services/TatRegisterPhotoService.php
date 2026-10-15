<?php

namespace App\Services;

use App\Models\TatRegister;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TatRegisterPhotoService
{
    private const DISK = 'local';

    private const DIRECTORY = 'tat-client-photos';

    public function store(TatRegister $record, UploadedFile $photo): string
    {
        $path = $photo->store(self::DIRECTORY.'/'.$record->getKey(), self::DISK);

        if ($path === false) {
            throw new \RuntimeException('Foto klien gagal disimpan.');
        }

        return $path;
    }

    public function exists(?string $path): bool
    {
        return $this->isManagedPath($path) && Storage::disk(self::DISK)->exists($path);
    }

    public function delete(?string $path): void
    {
        if ($this->isManagedPath($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk(self::DISK)->path($path);
    }

    public function mimeType(string $path): string
    {
        return Storage::disk(self::DISK)->mimeType($path) ?: 'application/octet-stream';
    }

    private function isManagedPath(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, self::DIRECTORY.'/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\');
    }
}
