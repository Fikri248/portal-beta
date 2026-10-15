<?php

namespace App\Http\Controllers;

use App\Enums\AuditLogAction;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Bnnk;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(Request $request): View
    {
        $perPage = Pagination::resolve($request);

        return view('users.index', [
            'users' => User::query()->with('bnnk')->orderBy('name')->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User,
            'bnnks' => Bnnk::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        $createdUser = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'bnnk_id' => $role === UserRole::Bnnk ? $validated['bnnk_id'] : null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        $this->auditLogService->record(
            AuditLogAction::UserCreated,
            entity: $createdUser,
            bnnkId: $createdUser->bnnk_id,
            newValues: $this->auditLogService->userSnapshot($createdUser),
            actor: $request->user(),
        );

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user->load('bnnk'),
            'bnnks' => Bnnk::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['role']);
        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role,
            'bnnk_id' => $role === UserRole::Bnnk ? $validated['bnnk_id'] : null,
            'is_active' => (bool) $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        if ($user->is($request->user()) && ! $attributes['is_active']) {
            return redirect()->route('users.index')->with('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        $oldValues = $this->auditLogService->userSnapshot($user);
        $user->update($attributes);
        $newValues = $this->auditLogService->userSnapshot($user->fresh());
        $changes = $this->auditLogService->changedValues($oldValues, $newValues);
        $auditBnnkId = $user->bnnk_id ?? ($oldValues['bnnk_id'] ?? null);

        if (($changes['old']['is_active'] ?? null) !== ($changes['new']['is_active'] ?? null)) {
            $statusAction = ($changes['new']['is_active'] ?? false)
                ? AuditLogAction::UserActivated
                : AuditLogAction::UserDeactivated;

            $this->auditLogService->record(
                $statusAction,
                entity: $user,
                bnnkId: $auditBnnkId,
                oldValues: ['is_active' => $changes['old']['is_active'] ?? null],
                newValues: ['is_active' => $changes['new']['is_active'] ?? null],
                actor: $request->user(),
            );

            unset($changes['old']['is_active'], $changes['new']['is_active']);
        }

        if ($changes['old'] !== [] || $changes['new'] !== [] || ! empty($validated['password'])) {
            $this->auditLogService->record(
                AuditLogAction::UserUpdated,
                entity: $user,
                bnnkId: $auditBnnkId,
                oldValues: $changes['old'],
                newValues: $changes['new'],
                metadata: empty($validated['password']) ? null : ['credential_updated' => true],
                actor: $request->user(),
            );
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && ! (bool) $validated['is_active']) {
            return redirect()->route('users.index')->with('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        $oldValue = (bool) $user->is_active;
        $user->update(['is_active' => (bool) $validated['is_active']]);

        if ($oldValue !== (bool) $user->is_active) {
            $this->auditLogService->record(
                $user->is_active ? AuditLogAction::UserActivated : AuditLogAction::UserDeactivated,
                entity: $user,
                bnnkId: $user->bnnk_id,
                oldValues: ['is_active' => $oldValue],
                newValues: ['is_active' => (bool) $user->is_active],
                actor: $request->user(),
            );
        }

        return redirect()->route('users.index')->with('success', $user->is_active ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.');
    }
}
