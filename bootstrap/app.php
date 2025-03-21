<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // 新增 API 路由註冊
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 為 API 路由添加中間件
        $middleware->api(append: [
            \App\Http\Middleware\CacheRequestFingerprint::class,
            \Illuminate\Http\Middleware\HandleCors::class, // 也可以同時添加 CORS 中間件
        ]);
        
        // 如果需要添加到 web 中間件組
        // $middleware->web(append: [
        //     // 要添加的中間件
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
