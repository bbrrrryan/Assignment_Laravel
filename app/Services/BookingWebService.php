<?php
/**
 * Author: Low Kim Hong
 */
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingWebService
{
    protected $baseUrl;
    protected $timeout = 10;
    public function __construct()
    {
        $bookingServiceConfig = config('services.booking_service', []);
        if (!empty($bookingServiceConfig['url'])) {
            $this->baseUrl = rtrim($bookingServiceConfig['url'], '/');
            $this->timeout = $bookingServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        $this->timeout = $bookingServiceConfig['timeout'] ?? $this->timeout;
    }

    public function getBookingInfo(int $bookingId): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/bookings/service/get-info';
        
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'booking_id' => $bookingId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
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
                $errorMessage = $data['message'] ?? 'Booking service returned an error.';
                Log::error('Booking Web Service returned error status', [
                    'booking_id' => $bookingId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("Booking Web Service error: {$errorMessage}");
            }
            
            return $data['data'];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Booking Web Service connection exception', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to Booking Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Booking Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            Log::error('Booking Web Service exception', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("Booking Web Service error: {$e->getMessage()}");
        }
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}

