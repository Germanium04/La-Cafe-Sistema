<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // inline role check — no separate file needed
        $middleware->alias([
            'staff.auth' => \App\Http\Middleware\StaffAuth::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'role'       => \App\Http\Middleware\RoleAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();