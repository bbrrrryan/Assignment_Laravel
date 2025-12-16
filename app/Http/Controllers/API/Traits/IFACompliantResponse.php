<?php

namespace App\Http\Controllers\API\Traits;

/**
 * Trait to provide IFA-compliant response methods
 * Ensures all responses include status and timestamp fields
 */
trait IFACompliantResponse
{
    /**
     * Return IFA-compliant success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200)
    {
        $response = [
            'status' => 'S', // IFA Standard: S (Success)
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return IFA-compliant fail response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function failResponse(string $message = 'Operation failed', $errors = null, int $statusCode = 400)
    {
        $response = [
            'status' => 'F', // IFA Standard: F (Fail)
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return IFA-compliant error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'An error occurred', $errors = null, int $statusCode = 500)
    {
        $response = [
            'status' => 'E', // IFA Standard: E (Error)
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}

