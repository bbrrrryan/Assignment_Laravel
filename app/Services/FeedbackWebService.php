<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FeedbackWebService
{
    protected $baseUrl;
    protected $timeout = 5;

    public function __construct()
    {
        $feedbackServiceConfig = config('services.feedback_service', []);
        if (!empty($feedbackServiceConfig['url'])) {
            $this->baseUrl = rtrim($feedbackServiceConfig['url'], '/');
            $this->timeout = $feedbackServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        $this->baseUrl = config('app.url', 'http://127.0.0.1:8000');
        $this->timeout = $feedbackServiceConfig['timeout'] ?? $this->timeout;
    }

    public function getFeedbacksByFacilityId(int $facilityId, ?int $userId = null, int $limit = 10): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/feedbacks/service/get-by-facility';
        
        try {
            $requestData = [
                'facility_id' => $facilityId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'limit' => $limit,
            ];
            
            if ($userId !== null) {
                $requestData['user_id'] = $userId;
            }
            
            $response = Http::timeout($this->timeout)->post($apiUrl, $requestData);
            
            if (!$response->successful()) {
                Log::error('Feedback Web Service HTTP request failed (getFeedbacksByFacilityId)', [
                    'facility_id' => $facilityId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "Feedback Web Service unavailable. HTTP Status: {$response->status()}. " .
                    "Response: {$response->body()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data']['feedbacks'])) {
                $errorMessage = $data['message'] ?? 'Feedback service returned an error.';
                Log::error('Feedback Web Service returned error status (getFeedbacksByFacilityId)', [
                    'facility_id' => $facilityId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("Feedback Web Service error: {$errorMessage}");
            }
            
            return $data['data']['feedbacks'] ?? [];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Feedback Web Service connection exception (getFeedbacksByFacilityId)', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to Feedback Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Feedback Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            Log::error('Feedback Web Service exception (getFeedbacksByFacilityId)', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("Feedback Web Service error: {$e->getMessage()}");
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

