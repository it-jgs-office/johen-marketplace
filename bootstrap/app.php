<?php

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
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'live-chat-admin' => \App\Http\Middleware\LiveChatAdminMiddleware::class,
            'live-chat-cs' => \App\Http\Middleware\LiveChatCsMiddleware::class,
            'jba-api-key' => \App\Http\Middleware\EnsureJbaApiKey::class,
        ]);

        // Halaman dinamis tidak boleh disimpan cache browser, supaya deploy
        // baru langsung terlihat di PWA yang sudah ter-install.
        $middleware->appendToGroup('web', \App\Http\Middleware\NoCacheHtml::class);

        $middleware->validateCsrfTokens(except: [
            'payment/notification',
            'digiflazz/callback',
            'service-worker.js',
            'pwa-version.json',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Callback ini menggantikan perilaku default, jadi expectsJson()
        // ikut ditambahkan agar request AJAX dari panel admin tetap
        // menerima respons JSON (422) dan bukan redirect HTML.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
