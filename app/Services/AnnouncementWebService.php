<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Announcement Web Service
 * 
 * Handles all communication with the User Management Module via Web Service API
 * for Announcement-related operations.
 * Web Service only - no database fallback mechanism.
 * Throws exceptions when Web Service is unavailable for testing purposes.
 */
class AnnouncementWebService
{
    /**
     * Base URL for the User Management Module
     */
    protected $baseUrl;

    /**
     * Timeout for HTTP requests (in seconds)
     */
    protected $timeout = 10;

    public function __construct()
    {
        // Get Announcement Service configuration
        $announcementServiceConfig = config('services.announcement_service', []);
        
        // If not set, fallback to User Service configuration (Announcement uses User Service)
        if (empty($announcementServiceConfig['url']) && empty($announcementServiceConfig['port'])) {
            $announcementServiceConfig = config('services.user_service', []);
        }
        
        // Priority 1: Use ANNOUNCEMENT_SERVICE_URL if explicitly set
        if (!empty($announcementServiceConfig['url'])) {
            $this->baseUrl = rtrim($announcementServiceConfig['url'], '/');
            $this->timeout = $announcementServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        
        // Priority 2: Use ANNOUNCEMENT_SERVICE_PORT if set (modify current URL's port)
        if (!empty($announcementServiceConfig['port'])) {
            $port = $announcementServiceConfig['port'];
            
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
            $this->timeout = $announcementServiceConfig['timeout'] ?? $this->timeout;
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
        $this->timeout = $announcementServiceConfig['timeout'] ?? $this->timeout;
    }

    /**
     * Get target user IDs based on audience criteria via Web Service only
     * Throws exception if Web Service is unavailable
     * 
     * @param string $targetAudience Target audience type: 'all', 'students', 'staff', 'admins', 'specific'
     * @param array|null $targetUserIds Specific user IDs (required if targetAudience is 'specific')
     * @return array Returns array of user IDs
     * @throws \Exception
     */
    public function getTargetUserIds(string $targetAudience, ?array $targetUserIds = null): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/users/service/get-ids';
        
        try {
            // Prepare request parameters based on target audience
            // IFA Standard: Include timestamp in request (mandatory requirement)
            $params = [
                'status' => 'active',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
            ];

            switch ($targetAudience) {
                case 'all':
                    // Get all active users
                    break;

                case 'students':
                    $params['role'] = 'student';
                    break;

                case 'staff':
                    $params['role'] = 'staff';
                    break;

                case 'admins':
                    $params['role'] = 'admin';
                    break;

                case 'specific':
                    // For specific users, use the provided user IDs
                    if (empty($targetUserIds)) {
                        return [];
                    }
                    $params['user_ids'] = $targetUserIds;
                    break;

                default:
                    return [];
            }

            // Make HTTP request to User Management Module (Inter-module Web Service call)
            $response = Http::timeout($this->timeout)->post($apiUrl, $params);

            if (!$response->successful()) {
                // HTTP request failed
                Log::error('Announcement Web Service: Failed to get user IDs from User Management Module', [
                    'target_audience' => $targetAudience,
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
            
            if (!isset($data['data']['user_ids']) || !is_array($data['data']['user_ids'])) {
                Log::error('Announcement Web Service: User Web Service returned invalid response', [
                    'target_audience' => $targetAudience,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service returned invalid response format");
            }
            
            return $data['data']['user_ids'];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('Announcement Web Service: User Web Service connection exception', [
                'target_audience' => $targetAudience,
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
            Log::error('Announcement Web Service: User Web Service exception', [
                'target_audience' => $targetAudience,
                'error' => $e->getMessage(),
                'url' => $apiUrl ?? 'unknown',
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

