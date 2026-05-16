<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\MaintenanceModeCheck;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*', headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->web(append: [
            SecurityHeaders::class,
            MaintenanceModeCheck::class,
        ]);
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) return null;
            return response()->view('errors.404', [], 404);
        });
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return response()->view('errors.419', [], 419);
        });
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            return response()->view('errors.403', [], 403);
        });
        $exceptions->render(function (HttpException $e, $request) {
            $status = $e->getStatusCode();
            if (in_array($status, [403, 404, 419, 500, 503], true) && view()->exists("errors.{$status}")) {
                return response()->view("errors.{$status}", [], $status);
            }
            return null;
        });
    })->create();
