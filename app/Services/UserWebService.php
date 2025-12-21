<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserWebService
{
    protected $baseUrl;
    protected $timeout = 5;

    public function __construct()
    {
        $userServiceConfig = config('services.user_service', []);
        if (!empty($userServiceConfig['url'])) {
            $this->baseUrl = rtrim($userServiceConfig['url'], '/');
            $this->timeout = $userServiceConfig['timeout'] ?? $this->timeout;
            return;
        }
        $this->timeout = $userServiceConfig['timeout'] ?? $this->timeout;
    }

    public function checkByPersonalId(string $personalId): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/users/service/check-by-personal-id';
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'personal_id' => $personalId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            if (!$response->successful()) {
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
                $errorMessage = $data['message'] ?? 'User service returned an error.';
                Log::error('User Web Service returned error status', [
                    'personal_id' => $personalId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service error: {$errorMessage}");
            }
            
            return [
                'exists' => $data['data']['exists'] ?? false,
                'user' => $data['data']['user'] ?? null,
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User Web Service connection exception', [
                'personal_id' => $personalId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to User Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'User Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
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
     * @param array $criteria Optional criteria: 'status', 'role', 'user_ids', 'personal_ids'
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
            
            if (isset($criteria['status'])) {
                $requestData['status'] = $criteria['status'];
            }
            
            if (isset($criteria['role'])) {
                $requestData['role'] = $criteria['role'];
            }
            
            if (isset($criteria['user_ids']) && is_array($criteria['user_ids'])) {
                $requestData['user_ids'] = $criteria['user_ids'];
            }
            
            if (isset($criteria['personal_ids']) && is_array($criteria['personal_ids'])) {
                $requestData['personal_ids'] = $criteria['personal_ids'];
            }
            
            $response = Http::timeout($this->timeout)->post($apiUrl, $requestData);
            
            if (!$response->successful()) {
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
                $errorMessage = $data['message'] ?? 'User service returned an error.';
                Log::error('User Web Service returned error status (getUserIds)', [
                    'criteria' => $criteria,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service error: {$errorMessage}");
            }
            
            return [
                'user_ids' => $data['data']['user_ids'] ?? [],
                'count' => $data['data']['count'] ?? 0,
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User Web Service connection exception (getUserIds)', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to User Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'User Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            Log::error('User Web Service exception (getUserIds)', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("User Web Service error: {$e->getMessage()}");
        }
    }

    public function getUserInfo(int $userId): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/users/service/get-info';
        
        try {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'user_id' => $userId,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
                Log::error('User Web Service HTTP request failed (getUserInfo)', [
                    'user_id' => $userId,
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
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data']['user'])) {
                $errorMessage = $data['message'] ?? 'User service returned an error.';
                Log::error('User Web Service returned error status (getUserInfo)', [
                    'user_id' => $userId,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service error: {$errorMessage}");
            }
            
            return $data['data']['user'];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User Web Service connection exception (getUserInfo)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to User Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'User Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            Log::error('User Web Service exception (getUserInfo)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception("User Web Service error: {$e->getMessage()}");
        }
    }

    public function getUsersInfo(array $userIds): array
    {
        $apiUrl = rtrim($this->baseUrl, '/') . '/api/users/service/get-users-info';
        
        try {
            $userIds = array_unique(array_map('intval', $userIds));
            
            if (empty($userIds)) {
                return [];
            }
            
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'user_ids' => $userIds,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$response->successful()) {
                Log::error('User Web Service HTTP request failed (getUsersInfo)', [
                    'user_ids' => $userIds,
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
            
            if (!isset($data['status']) || $data['status'] !== 'S' || !isset($data['data']['users'])) {
                $errorMessage = $data['message'] ?? 'User service returned an error.';
                Log::error('User Web Service returned error status (getUsersInfo)', [
                    'user_ids' => $userIds,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service error: {$errorMessage}");
            }
            
            $usersArray = $data['data']['users'] ?? [];
            $usersMap = [];
            foreach ($usersArray as $user) {
                if (isset($user['id'])) {
                    $usersMap[$user['id']] = $user;
                }
            }
            
            return $usersMap;
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User Web Service connection exception (getUsersInfo)', [
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            throw new \Exception(
                "Unable to connect to User Web Service: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'User Web Service') !== false || 
                strpos($e->getMessage(), 'Unable to connect') !== false) {
                throw $e;
            }
            
            Log::error('User Web Service exception (getUsersInfo)', [
                'user_ids' => $userIds,
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

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
