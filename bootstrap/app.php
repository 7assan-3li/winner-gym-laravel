<?php

use App\Http\Middleware\EnsureAnyGymPermission;
use App\Http\Middleware\EnsureGymPermission;
use App\Http\Middleware\EnsureOwner;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->validateCsrfTokens(except: [
            'member-inquiry',
            'login',
            'logout',
        ]);

        $middleware->alias([
            'gym.permission' => EnsureGymPermission::class,
            'gym.any' => EnsureAnyGymPermission::class,
            'gym.owner' => EnsureOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
