<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias(['locale' => SetLocale::class, 'security' => SecurityHeaders::class]);
        // Before throttling and route-model binding, so rate-limit messages and the
        // 404 for an unknown slug are already in the visitor's language.
        $middleware->prependToPriorityList(before: [ThrottleRequests::class, SubstituteBindings::class], prepend: SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 403/404/429/500/503 as designed pages; 419 back to the form with a message.
        $exceptions->respond(new ErrorPage);
    })->create();
