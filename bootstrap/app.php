<?php

use App\Http\Middleware\AssignRequestId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $code = match ($status) {
                401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 409 => 'CONFLICT', 422 => 'VALIDATION_FAILED', 429 => 'TOO_MANY_REQUESTS', default => 'SERVER_ERROR'
            };
            $message = match (true) {
                $exception instanceof ValidationException => $exception->getMessage(),
                $status === 401 => 'Your session has expired. Please sign in again.',
                $status === 403 => 'You do not have permission to perform this action.',
                $status === 404 => 'The requested record was not found.',
                $status === 429 => 'Too many requests. Please wait and try again.',
                $status >= 500 && ! config('app.debug') => 'An unexpected error occurred.',
                default => $exception->getMessage() ?: 'The request could not be completed.',
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message, 'field_errors' => $exception instanceof ValidationException ? $exception->errors() : null], 'meta' => ['request_id' => $request->attributes->get('request_id'), 'timestamp' => now()->toIso8601String()]], $status);
        });
    })->create();
