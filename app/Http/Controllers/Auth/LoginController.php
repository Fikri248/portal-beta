<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditLogAction;
use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.']);
        }

        $request->session()->regenerate();

        $this->auditLogService->record(
            AuditLogAction::AuthLogin,
            actor: $user,
            bnnkId: $user->bnnk_id,
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->auditLogService->record(
            AuditLogAction::AuthLogout,
            actor: $user,
            bnnkId: $user?->bnnk_id,
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
