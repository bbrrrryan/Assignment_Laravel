<?php
/**
 * Author: Ng Jhun Hou
 */ 
namespace App\Services;
use App\Models\Facility;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacilityWebService
{
    protected $baseUrl;
    protected $timeout = 5;
    public function __construct()
    {
        $facilityServiceConfig = config('services.facility_service', []);
        if (!empty($facilityServiceConfig['url'])) {
            $this->baseUrl = rtrim($facilityServiceConfig['url'], '/');
            $this->timeout = $facilityServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        $this->timeout = $facilityServiceConfig['timeout'] ?? $this->timeout;
    }
    public function getFacilityInfo(int $facilityId): Facility
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/facilities/service/get-info';
        
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'facility_id' => $facilityId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
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
                $errorMessage = $data['message'] ?? 'Facility service returned an error.';
                Log::error('Facility Web Service returned error status', [
                    'facility_id' => $facilityId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("Facility Web Service error: {$errorMessage}");
            }
            $facilityData = $data['data']['facility'];
            
            if (is_array($facilityData)) {
                $facility = new Facility();
                $facility->fill($facilityData);
                $facility->exists = true;
            } else {
                throw new \Exception("Invalid facility data format from Web Service");
            }
            return $facility;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facility Web Service connection exception', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            throw new \Exception(
                "Unable to connect to Facility Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Facility Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            Log::error('Facility Web Service exception', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("Facility Web Service error: {$e->getMessage()}");
        }
    }

    
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

