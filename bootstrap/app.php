<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\ApiException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',            // matches the spec's base URL
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'resolve.user' => \App\Http\Middleware\ResolveCurrentUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json(['error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $e->errors(),
            ]], 400);
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return response()->json(['error' => [
                'code' => 'NOT_FOUND',
                'message' => 'The requested resource does not exist.',
                'details' => null,
            ]], 404);
        });

        // Route model binding converts ModelNotFoundException into
        // NotFoundHttpException before it reaches the handler, so we
        // render the same custom JSON shape for both.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return response()->json(['error' => [
                'code' => 'NOT_FOUND',
                'message' => 'The requested resource does not exist.',
                'details' => null,
            ]], 404);
        });

        $exceptions->render(function (ApiException $e, $request) {
            return response()->json(['error' => [
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                'details' => $e->details,
            ]], $e->status);
        });
    })->create();