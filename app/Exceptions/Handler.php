<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

   
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });
    }

    
    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException && $request->is('api/*')) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 422);
        }

        return parent::render($request, $e);
    }
}
