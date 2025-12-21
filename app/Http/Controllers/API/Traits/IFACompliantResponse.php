<?php

namespace App\Http\Controllers\API\Traits;


trait IFACompliantResponse
{

    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200)
    {
        $response = [
            'status' => 'S', 
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    
    protected function failResponse(string $message = 'Operation failed', $errors = null, int $statusCode = 400)
    {
        $response = [
            'status' => 'F', 
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    
    protected function errorResponse(string $message = 'An error occurred', $errors = null, int $statusCode = 500)
    {
        $response = [
            'status' => 'E', 
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}

