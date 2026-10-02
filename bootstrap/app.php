<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerifyJwtToken;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            // VerifyJwtToken::class, // Removed to avoid global application
        ]);

        // Gateways POST here from outside our session (eSewa/Khalti browser
        // return, Fonepay server webhook) — they can't carry our CSRF token.
        // Every field from these routes is still treated as untrusted and
        // verified by the driver (signature check / status-check call).
        $middleware->validateCsrfTokens(except: [
            'pay/*/callback/*',
        ]);

        $middleware->alias([
            'jwt.verify' => VerifyJwtToken::class,
            'permission' => CheckPermission::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
    $schedule->command('event:update-event-status')
        ->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();