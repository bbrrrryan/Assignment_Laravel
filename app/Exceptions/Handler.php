<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
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
    }

    /**
     * Render an exception into an HTTP response.
     * Override to provide IFA-compliant responses for API routes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle ValidationException for API routes with IFA-compliant format
        if ($e instanceof ValidationException && $request->is('api/*')) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 422);
        }

        return parent::render($request, $e);
    }
}
