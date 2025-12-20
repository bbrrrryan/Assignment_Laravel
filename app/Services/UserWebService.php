<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * User Web Service
 * 
 * Handles all communication with the User Management Module via Web Service API.
 * Web Service only - no database fallback mechanism.
 * Throws exceptions when Web Service is unavailable for testing purposes.
 */
class UserWebService
{
    /**
     * Base URL for the User Management Module
     */
    protected $baseUrl;

    /**
     * Timeout for HTTP requests (in seconds)
     */
    protected $timeout = 5;

    public function __construct()
    {
        // Get User Service configuration
        $userServiceConfig = config('services.user_service', []);
        
        // Priority 1: Use USER_SERVICE_URL if explicitly set
        if (!empty($userServiceConfig['url'])) {
            $this->baseUrl = rtrim($userServiceConfig['url'], '/');
            $this->timeout = $userServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        
        // Priority 2: Use USER_SERVICE_PORT if set (modify current URL's port)
        if (!empty($userServiceConfig['port'])) {
            $port = $userServiceConfig['port'];
            
            // Try to get URL from current request
            if (request()->hasHeader('Host')) {
                $host = request()->getHost();
                $scheme = request()->getScheme();
                $this->baseUrl = "{$scheme}://{$host}:{$port}";
            } else {
                // Use APP_URL and replace port
                $appUrl = config('app.url', 'http://127.0.0.1:8000');
                $parsedUrl = parse_url($appUrl);
                $scheme = $parsedUrl['scheme'] ?? 'http';
                $host = $parsedUrl['host'] ?? '127.0.0.1';
                $this->baseUrl = "{$scheme}://{$host}:{$port}";
            }
            $this->timeout = $userServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        
        // Priority 3: Use current request URL
        if (request()->hasHeader('Host')) {
            $host = request()->getHost();
            $scheme = request()->getScheme();
            $port = request()->getPort();
            
            if ($port && $port != 80 && $port != 443) {
                $this->baseUrl = "{$scheme}://{$host}:{$port}";
            } else {
                $this->baseUrl = "{$scheme}://{$host}";
            }
        } else {
            // Priority 4: Fallback to APP_URL
            $configUrl = config('app.url', 'http://127.0.0.1:8000');
            
            // Replace localhost with 127.0.0.1 to avoid DNS issues
            if (str_contains($configUrl, 'localhost')) {
                $this->baseUrl = str_replace('localhost', '127.0.0.1', $configUrl);
            } else {
                $this->baseUrl = $configUrl;
            }
            
            // Ensure port 8000 is included if not specified
            if (!str_contains($this->baseUrl, ':8000') && 
                !str_contains($this->baseUrl, ':80') && 
                !str_contains($this->baseUrl, ':443') &&
                !str_contains($this->baseUrl, 'https://')) {
                $this->baseUrl = rtrim($this->baseUrl, '/') . ':8000';
            }
        }
        
        // Set timeout from config if available
        $this->timeout = $userServiceConfig['timeout'] ?? $this->timeout;
    }

    /**
     * Check if user exists by personal_id via Web Service only
     * Throws exception if Web Service is unavailable
     * 
     * @param string $personalId
     * @return array Returns ['exists' => bool, 'user' => array|null]
     * @throws \Exception
     */
    public function checkByPersonalId(string $personalId): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/users/service/check-by-personal-id';
        
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'personal_id' => $personalId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
                // HTTP request failed
                Log::error('User Web Service HTTP request failed', [
                    'personal_id' => $personalId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "User Web Service unavailable. HTTP Status: {$response->status()}. " .
                    "Response: {$response->body()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data'])) {
                // Web Service returned error status
                $errorMessage = $data['message'] ?? 'User service returned an error.';
                Log::error('User Web Service returned error status', [
                    'personal_id' => $personalId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service error: {$errorMessage}");
            }
            
            // Return data directly from Web Service response
            return [
                'exists' => $data['data']['exists'] ?? false,
                'user' => $data['data']['user'] ?? null,
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('User Web Service connection exception', [
                'personal_id' => $personalId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to User Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            // Re-throw if it's already our custom exception
            if (strpos($e->getMessage(), 'User Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            // Other exceptions
            Log::error('User Web Service exception', [
                'personal_id' => $personalId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("User Web Service error: {$e->getMessage()}");
        }
    }

    /**
     * Get user IDs by criteria via Web Service only
     * Throws exception if Web Service is unavailable
     * 
     * @param array $criteria Optional criteria: 'status', 'role', 'user_ids'
     * @return array Returns ['user_ids' => array, 'count' => int]
     * @throws \Exception
     */
    public function getUserIds(array $criteria = []): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/users/service/get-ids';
        
        try {
            $requestData = [
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];
            
            // Add optional criteria
            if (isset($criteria['status'])) {
                $requestData['status'] = $criteria['status'];
            }
            
            if (isset($criteria['role'])) {
                $requestData['role'] = $criteria['role'];
            }
            
            if (isset($criteria['user_ids']) && is_array($criteria['user_ids'])) {
                $requestData['user_ids'] = $criteria['user_ids'];
            }
            
            $response = Http::timeout($this->timeout)->post($apiUrl, $requestData);
            
            if (!$response->successful()) {
                // HTTP request failed
                Log::error('User Web Service HTTP request failed (getUserIds)', [
                    'criteria' => $criteria,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "User Web Service unavailable. HTTP Status: {$response->status()}. " .
                    "Response: {$response->body()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data'])) {
                // Web Service returned error status
                $errorMessage = $data['message'] ?? 'User service returned an error.';
                Log::error('User Web Service returned error status (getUserIds)', [
                    'criteria' => $criteria,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service error: {$errorMessage}");
            }
            
            // Return data directly from Web Service response
            return [
                'user_ids' => $data['data']['user_ids'] ?? [],
                'count' => $data['data']['count'] ?? 0,
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('User Web Service connection exception (getUserIds)', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to User Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            // Re-throw if it's already our custom exception
            if (strpos($e->getMessage(), 'User Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            // Other exceptions
            Log::error('User Web Service exception (getUserIds)', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("User Web Service error: {$e->getMessage()}");
        }
    }

    /**
     * Set timeout for HTTP requests
     * 
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get current timeout
     * 
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}

