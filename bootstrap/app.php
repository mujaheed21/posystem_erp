<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * The Key Fix for Laravel 11 + Sanctum:
         * This enables Session and Cookie middleware for your API routes,
         * resolving the "Session store not set" error.
         */
        $middleware->statefulApi();

        $middleware->alias([
            'warehouse.access' => \App\Http\Middleware\EnsureWarehouseAccess::class,
            // Spatie permission middlewares
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    
        // Allows API calls without CSRF tokens for pure API endpoints
        // However, Sanctum's /login still uses the CSRF cookie we fetched
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();