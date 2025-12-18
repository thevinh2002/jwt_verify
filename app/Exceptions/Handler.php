<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error('Validation error', 422, $e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated', 401);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error('Not found', 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                $message = $e->getMessage() !== '' ? $e->getMessage() : 'Error';

                return ApiResponse::error($message, $e->getStatusCode());
            }

            if (config('app.debug')) {
                return ApiResponse::error($e->getMessage(), 500, [
                    'exception' => get_class($e),
                ]);
            }

            return ApiResponse::error('Server error', 500);
        });
    }
}
