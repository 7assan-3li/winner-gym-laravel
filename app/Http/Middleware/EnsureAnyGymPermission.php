<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyGymPermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        abort_unless($user && collect($abilities)->contains(
            fn (string $ability) => $this->permissions->allows($user, $ability)
        ), 403);

        return $next($request);
    }
}
