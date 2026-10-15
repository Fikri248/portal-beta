<?php

namespace App\Http\Controllers;

use App\Enums\AuditLogAction;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('bnnk'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldValues = $this->auditLogService->userSnapshot($user);
        $user->update($request->validated());
        $changes = $this->auditLogService->changedValues($oldValues, $this->auditLogService->userSnapshot($user->fresh()));

        $this->auditLogService->record(
            AuditLogAction::ProfileUpdated,
            entity: $user,
            bnnkId: $user->bnnk_id,
            oldValues: $changes['old'],
            newValues: $changes['new'],
            actor: $user,
        );

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }
}
