<?php

namespace App\Http\Middleware;

use App\Enums\AuditLogAction;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! $request->user()->is_active) {
            $this->auditLogService->record(
                AuditLogAction::AuthLogout,
                actor: $request->user(),
                bnnkId: $request->user()->bnnk_id,
                description: 'Logout otomatis karena akun nonaktif.',
            );
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.']);
        }

        return $next($request);
    }
}
