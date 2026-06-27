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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.verified' => \App\Http\Middleware\EnsureVerified::class,
            'ensure.pin'      => \App\Http\Middleware\EnsurePinIsSet::class,
            'admin'           => \App\Http\Middleware\EnsureAdmin::class,
            'ensure.not_locked' => \App\Http\Middleware\EnsureNotLocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
