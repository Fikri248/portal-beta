<?php

namespace App\Http\Controllers;

use App\Enums\AuditLogAction;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function edit(): View
    {
        return view('profile.password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        $this->auditLogService->record(
            AuditLogAction::PasswordChanged,
            entity: $user,
            bnnkId: $user->bnnk_id,
            metadata: ['credential_updated' => true],
            actor: $user,
        );

        return redirect()->route('password.edit')->with('success', 'Password berhasil diubah.');
    }
}
