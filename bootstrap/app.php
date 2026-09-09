<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 👇 CORS middleware එක API routes එකට add කරන්න
        $middleware->api(prepend: [
            Cors::class,
        ]);

        // 👇 Global middleware ලෙසත් add කරන්න (අමතර ආරක්ෂාව සඳහා)
        $middleware->append([
            Cors::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'cors' => Cors::class, // 👈 මෙය add කරන්න
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();