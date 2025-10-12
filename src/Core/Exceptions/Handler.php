<?php

namespace Core\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Handle API exceptions and return consistent JSON response
     */
    public function handleApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'status' => 0,
                'message' => 'Not Found'
            ], 404);
        }

        // Handle other exceptions
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        if (in_array($e->getMessage(), ['Unauthorized', 'Unauthenticated.'])) {
            $statusCode = 401;
        }

        return response()->json([
            'status' => 0,
            'message' => $e->getMessage() ?: 'Server Error',
        ], $statusCode);
    }
}