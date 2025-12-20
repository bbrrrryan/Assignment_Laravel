<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Booking Web Service
 * 
 * Handles all communication with the Booking Module via Web Service API.
 * Web Service only - no database fallback mechanism.
 * Throws exceptions when Web Service is unavailable for testing purposes.
 */
class BookingWebService
{
    /**
     * Base URL for the Booking Module
     */
    protected $baseUrl;

    /**
     * Timeout for HTTP requests (in seconds)
     */
    protected $timeout = 10;

    public function __construct()
    {
        // Get Booking Service configuration
        $bookingServiceConfig = config('services.booking_service', []);
        
        // Priority 1: Use BOOKING_SERVICE_URL if explicitly set
        if (!empty($bookingServiceConfig['url'])) {
            $this->baseUrl = rtrim($bookingServiceConfig['url'], '/');
            $this->timeout = $bookingServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        
        // Priority 2: Use BOOKING_SERVICE_PORT if set (modify current URL's port)
        if (!empty($bookingServiceConfig['port'])) {
            $port = $bookingServiceConfig['port'];
            
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
            $this->timeout = $bookingServiceConfig['timeout'] ?? $this->timeout;
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
        $this->timeout = $bookingServiceConfig['timeout'] ?? $this->timeout;
    }

    /**
     * Get booking information by booking_id via Web Service only
     * Throws exception if Web Service is unavailable
     * 
     * @param int $bookingId
     * @return array Returns booking information array
     * @throws \Exception
     */
    public function getBookingInfo(int $bookingId): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/bookings/service/get-info';
        
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'booking_id' => $bookingId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
                // HTTP request failed
                Log::error('Booking Web Service HTTP request failed', [
                    'booking_id' => $bookingId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "Booking Web Service unavailable. HTTP Status: {$response->status()}. " .
                    "Response: {$response->body()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data'])) {
                // Web Service returned error status
                $errorMessage = $data['message'] ?? 'Booking service returned an error.';
                Log::error('Booking Web Service returned error status', [
                    'booking_id' => $bookingId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("Booking Web Service error: {$errorMessage}");
            }
            
            // Return data directly from Web Service response
            return $data['data'];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('Booking Web Service connection exception', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to Booking Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            // Re-throw if it's already our custom exception
            if (strpos($e->getMessage(), 'Booking Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            // Other exceptions
            Log::error('Booking Web Service exception', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("Booking Web Service error: {$e->getMessage()}");
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

