<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'not_blocked' => \App\Http\Middleware\EnsureUserNotBlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom Validation Errors (Halaman 14)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                $failed = $e->validator->failed();
                $errors = $e->validator->errors();
                $violations = [];

                foreach ($failed as $field => $rules) {
                    foreach ($rules as $rule => $params) {
                        $ruleLower = strtolower($rule);
                        if ($ruleLower === 'required') {
                            $msg = 'required';
                        } elseif ($ruleLower === 'min') {
                            $limit = $params['allowed'][0] ?? $params['min'] ?? 0;
                            $msg = 'must be at least ' . $limit . ' characters long';
                        } elseif ($ruleLower === 'max') {
                            $limit = $params['allowed'][0] ?? $params['max'] ?? 0;
                            $msg = 'must be at most ' . $limit . ' characters long';
                        } else {
                            // Fallback
                            $msg = $errors->first($field);
                        }
                        $violations[$field] = [
                            'message' => $msg
                        ];
                        break; // At most one validation per field
                    }
                }

                return response()->json([
                    'status' => 'invalid',
                    'message' => 'Request body is not valid.',
                    'violations' => $violations
                ], 400);
            }
        });

        // Custom Authentication Errors (Halaman 15)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                $authHeader = $request->header('Authorization');
                // Check also for Sanctum's query or form token if header is missing
                $tokenParam = $request->input('token');

                if (!$authHeader && !$tokenParam) {
                    return response()->json([
                        'status' => 'unauthenticated',
                        'message' => 'Missing token'
                    ], 401);
                }

                return response()->json([
                    'status' => 'unauthenticated',
                    'message' => 'Invalid token'
                ], 401);
            }
        });

        // Custom Not Found Errors (Halaman 16)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'not-found',
                    'message' => 'Not found'
                ], 404);
            }
        });
    })->create();
