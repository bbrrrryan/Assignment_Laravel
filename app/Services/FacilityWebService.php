<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Facility Web Service
 * 
 * Handles all communication with the Facility Management Module via Web Service API.
 * Web Service only - no database fallback mechanism.
 * Throws exceptions when Web Service is unavailable for testing purposes.
 */
class FacilityWebService
{
    /**
     * Base URL for the Facility Management Module
     */
    protected $baseUrl;

    /**
     * Timeout for HTTP requests (in seconds)
     */
    protected $timeout = 5;

    public function __construct()
    {
        // Get Facility Service configuration
        $facilityServiceConfig = config('services.facility_service', []);
        
        // Priority 1: Use FACILITY_SERVICE_URL if explicitly set
        if (!empty($facilityServiceConfig['url'])) {
            $this->baseUrl = rtrim($facilityServiceConfig['url'], '/');
            $this->timeout = $facilityServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        
        // Priority 2: Use FACILITY_SERVICE_PORT if set (modify current URL's port)
        if (!empty($facilityServiceConfig['port'])) {
            $port = $facilityServiceConfig['port'];
            
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
            $this->timeout = $facilityServiceConfig['timeout'] ?? $this->timeout;
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
        $this->timeout = $facilityServiceConfig['timeout'] ?? $this->timeout;
    }

    /**
     * Get facility information via Web Service only
     * Throws exception if Web Service is unavailable
     * 
     * @param int $facilityId
     * @return Facility
     * @throws \Exception
     */
    public function getFacilityInfo(int $facilityId): Facility
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/facilities/service/get-info';
        
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'facility_id' => $facilityId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
                // HTTP request failed
                Log::error('Facility Web Service HTTP request failed', [
                    'facility_id' => $facilityId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "Facility Web Service unavailable. HTTP Status: {$response->status()}. " .
                    "Response: {$response->body()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data']['facility'])) {
                // Web Service returned error status
                $errorMessage = $data['message'] ?? 'Facility service returned an error.';
                Log::error('Facility Web Service returned error status', [
                    'facility_id' => $facilityId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("Facility Web Service error: {$errorMessage}");
            }
            
            // Use facility data directly from Web Service response (no database query)
            $facilityData = $data['data']['facility'];
            
            // Convert array to Facility model instance
            if (is_array($facilityData)) {
                $facility = new Facility();
                $facility->fill($facilityData);
                // Set exists to true to prevent save operations
                $facility->exists = true;
            } else {
                throw new \Exception("Invalid facility data format from Web Service");
            }
            
            return $facility;
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('Facility Web Service connection exception', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to Facility Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            // Re-throw if it's already our custom exception
            if (strpos($e->getMessage(), 'Facility Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            // Other exceptions
            Log::error('Facility Web Service exception', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("Facility Web Service error: {$e->getMessage()}");
        }
    }

    /**
     * Check facility availability via Web Service only
     * Throws exception if Web Service is unavailable
     * 
     * @param int $facilityId
     * @param string $date
     * @param string|null $startTime
     * @param string|null $endTime
     * @param int|null $expectedAttendees
     * @return array
     * @throws \Exception
     */
    public function checkAvailability(
        int $facilityId,
        string $date,
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $expectedAttendees = null
    ): array {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/facilities/service/check-availability';
        
        try {
            $payload = [
                'facility_id' => $facilityId,
                'date' => $date,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];
            
            if ($startTime) {
                $payload['start_time'] = $startTime;
            }
            
            if ($endTime) {
                $payload['end_time'] = $endTime;
            }
            
            if ($expectedAttendees) {
                $payload['expected_attendees'] = $expectedAttendees;
            }
            
            $response = Http::timeout($this->timeout)->post($apiUrl, $payload);
            
            if (!$response->successful()) {
                Log::error('Facility availability check Web Service HTTP request failed', [
                    'facility_id' => $facilityId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "Facility Availability Web Service unavailable. HTTP Status: {$response->status()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'S') {
                $errorMessage = $data['message'] ?? 'Availability service returned an error.';
                Log::error('Facility availability check Web Service returned error status', [
                    'facility_id' => $facilityId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("Facility Availability Web Service error: {$errorMessage}");
            }
            
            return $data['data'] ?? [];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facility availability check Web Service connection exception', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to Facility Availability Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            // Re-throw if it's already our custom exception
            if (strpos($e->getMessage(), 'Facility Availability Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            Log::error('Facility availability check Web Service exception', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("Facility Availability Web Service error: {$e->getMessage()}");
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

