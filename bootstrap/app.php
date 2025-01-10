<?php

use App\Http\Middleware\CustomCheckAbility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/api/unauthenticated');

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CustomCheckAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handling NotFoundHttpException
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Resource not found.'
            ], 404);
        });

        // Handling MethodNotAllowedHttpException
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Method not allowed.'
            ], 405);
        });

        // Handling AuthenticationException (unauthenticated)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        });

        // Handling AuthorizationException (unauthorized access)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json([
                'message' => 'Unauthorized access.'
            ], 403);
        });

        // Handling ValidationException (input validation errors)
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        });

        // Handling QueryException (SQL query errors, e.g. database issues)
        // $exceptions->render(function (QueryException $e, Request $request) {
        //     return response()->json([
        //         'message' => 'Database query error.',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // });

        // Handling ModelNotFoundException (for models not found)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return response()->json([
                'message' => 'Model not found.',
                'error' => $e->getModel(),
            ], 404);
        });

        // Handling ThrottleRequestsException (rate limiting)
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        });

        // Handling NotAcceptableHttpException (client sent unacceptable content type)
        $exceptions->render(function (NotAcceptableHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Not acceptable.',
            ], 406);
        });

        // Handling MethodNotAllowedHttpException (method not allowed)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return response()->json([
                'message' => 'HTTP method not allowed.',
            ], 405);
        });

        // Handling BadRequestHttpException (malformed request)
        $exceptions->render(function (BadRequestHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Bad request.',
            ], 400);
        });

        // Handling general Exception (500 Internal Server Error)
        // $exceptions->render(function (Exception $e, Request $request) {
        //     return response()->json([
        //         'message' => 'Something went wrong.',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // });
    })->create();
