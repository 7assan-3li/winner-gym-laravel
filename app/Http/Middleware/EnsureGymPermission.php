<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGymPermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless($request->user() && $this->permissions->allows($request->user(), $ability), 403);

        return $next($request);
    }
}
