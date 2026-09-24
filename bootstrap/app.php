<?php

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\TransactionAlreadyReversedException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $throwable, Request $request) {
            $status = match (true) {
                $throwable instanceof TransactionAlreadyReversedException => 409,
                $throwable instanceof InsufficientBalanceException,
                $throwable instanceof InvalidTransactionException => 422,
                default => null,
            };

            if ($status !== null) {
                if ($request->header('X-Inertia')) {
                    Inertia::flash('toast', [
                        'type' => 'error',
                        'message' => $throwable->getMessage(),
                    ]);

                    return redirect()->back();
                }

                return response()->json([
                    'message' => $throwable->getMessage(),
                ], $status);
            }

            return null;
        });
    })->create();
