<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to validate IFA standard request metadata
 * Ensures all API requests include timestamp or requestID
 */
class ValidateRequestMetadata
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for GET requests (make timestamp/requestID optional)
        // For POST/PUT/PATCH/DELETE, validate or auto-add metadata
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // If neither timestamp nor requestID is provided, auto-add them
            if (!$request->has('timestamp') && !$request->has('requestID')) {
                $request->merge([
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'requestID' => uniqid('req_', true),
                ]);
            } elseif ($request->has('timestamp')) {
                // Validate timestamp format if provided
                $timestamp = $request->input('timestamp');
                if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp)) {
                    return response()->json([
                        'status' => 'F',
                        'message' => 'Invalid timestamp format. Required format: YYYY-MM-DD HH:MM:SS',
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ], 422);
                }
            }
        } else {
            // For GET requests, auto-add if missing (optional but recommended)
            if (!$request->has('timestamp') && !$request->has('requestID')) {
                $request->merge([
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'requestID' => uniqid('req_', true),
                ]);
            }
        }

        return $next($request);
    }
}

