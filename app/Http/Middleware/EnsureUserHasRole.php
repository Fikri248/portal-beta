<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $allowed = array_map(fn (string $r) => UserRole::tryFrom($r), $roles);

        if (! in_array($user->role, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
