<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Factories\AnnouncementFactory;
use App\Models\Announcement;
use App\Models\User;
use App\Services\UserWebService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    protected $userWebService;

    public function __construct(UserWebService $userWebService)
    {
        $this->userWebService = $userWebService;
    }
    public function index(Request $request)
    {
        $query = Announcement::with('creator')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->when($request->has('search'), function($q) use ($request) {
                $search = $request->search;
                $q->where(function($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                          ->orWhere('content', 'like', "%{$search}%");
                });
            });
        
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['id', 'title', 'type', 'priority', 'created_at', 'is_active'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (strtolower($sortOrder) !== 'asc' && strtolower($sortOrder) !== 'desc') {
            $sortOrder = 'desc';
        }
        
        $announcements = $query->orderBy($sortBy, $sortOrder)
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'S',
            'message' => 'Announcements retrieved successfully',
            'data' => $announcements,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,error,reminder,general',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'target_audience' => 'required|in:all,students,staff,admins,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'target_personal_ids' => 'nullable|array',
            'target_personal_ids.*' => 'string',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_active' => 'nullable|boolean',
        ]);

        // If target_personal_ids is provided, convert to user_ids via Web Service
        $targetUserIds = $validated['target_user_ids'] ?? null;
        if ($validated['target_audience'] === 'specific' && !empty($validated['target_personal_ids'])) {
            try {
                $result = $this->userWebService->getUserIds([
                    'status' => 'active',
                    'personal_ids' => $validated['target_personal_ids']
                ]);
                $targetUserIds = $result['user_ids'] ?? [];
            } catch (\Exception $e) {
                Log::error('Failed to convert personal_ids to user_ids via Web Service', [
                    'personal_ids' => $validated['target_personal_ids'],
                    'error' => $e->getMessage(),
                ]);
                
                return response()->json([
                    'status' => 'F',
                    'message' => 'Unable to retrieve user information. The user service is currently unavailable. Please try again later.',
                    'error_details' => $e->getMessage(),
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 503);
            }
        }

        $announcement = AnnouncementFactory::makeAnnouncement(
            $validated['type'],
            $validated['title'],
            $validated['content'],
            $validated['target_audience'],
            auth()->id(),
            $validated['priority'] ?? null,
            $targetUserIds,
            $validated['published_at'] ?? null,
            $validated['expires_at'] ?? null,
            $request->is_active ?? true
        );

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement created successfully',
            'data' => $announcement->load('creator'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function show(string $id)
    {
        $announcement = Announcement::with(['creator', 'users'])->findOrFail($id);

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement retrieved successfully',
            'data' => $announcement,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:info,warning,success,error,reminder,general',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'target_audience' => 'sometimes|required|in:all,students,staff,admins,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement->update($validated);

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement updated successfully',
            'data' => $announcement->load('creator'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function destroy(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function publish(string $id)
    {
        $announcement = Announcement::findOrFail($id);

        if (!$announcement->is_active) {
            return response()->json([
                'status' => 'F',
                'message' => 'Announcement is not active',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 400);
        }

        try {
            // Get target user IDs via Web Service only (no database fallback)
            $targetUsers = $this->getTargetUsers(
                $announcement->target_audience,
                $announcement->target_user_ids
            );
        } catch (\Exception $e) {
            Log::error('Failed to get target users for announcement via Web Service', [
                'announcement_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => 'F',
                'message' => 'Unable to retrieve target users. The user service is currently unavailable. Please try again later.',
                'error_details' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 503);
        }

        $syncData = [];
        foreach ($targetUsers as $userId) {
            $syncData[$userId] = [
                'is_read' => false,
            ];
        }

        $announcement->users()->sync($syncData);

        if (!$announcement->published_at) {
            $announcement->update(['published_at' => now()]);
        }

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement published successfully',
            'data' => [
                'announcement' => $announcement,
                'recipients_count' => count($targetUsers),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function unreadCount()
    {
        $user = auth()->user();
        $count = $user->announcements()
            ->where('is_active', true)
            ->wherePivot('is_read', false)
            ->count();

        return response()->json([
            'status' => 'S',
            'message' => 'Unread announcements count retrieved successfully',
            'data' => [
                'count' => $count,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function myAnnouncements(Request $request)
    {
        $user = auth()->user();

        $query = $user->announcements()
            ->with('creator')
            ->where('is_active', true)
            ->when($request->is_read !== null, function($q) use ($request) {
                $q->wherePivot('is_read', $request->is_read);
            })
            ->when($request->type, fn($q) => $q->where('type', $request->type));

        $announcements = $query->latest('pivot_created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'S',
            'message' => 'My announcements retrieved successfully',
            'data' => $announcements,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function markAsRead(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        if (!$user->announcements()->where('announcements.id', $id)->exists()) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Announcement not found for this user',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 404);
        }

        $user->announcements()->updateExistingPivot($id, [
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement marked as read',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function markAsUnread(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        if (!$announcement->is_active || !$announcement->published_at) {
            return response()->json([
                'status' => 'F',
                'message' => 'Announcement is not published',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        if ($announcement->expires_at && $announcement->expires_at->isPast()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Announcement has expired',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        $user = auth()->user();
        $targetAudience = $announcement->target_audience;
        $role = strtolower($user->role ?? '');
        $shouldSee = false;

        // Staff and students have same privilege, so they see the same announcements
        if ($targetAudience === 'all') {
            $shouldSee = true;
        } elseif ($targetAudience === 'students' && ($role === 'student' || $role === 'staff')) {
            $shouldSee = true;
        } elseif ($targetAudience === 'staff' && ($role === 'student' || $role === 'staff')) {
            $shouldSee = true;
        } elseif ($targetAudience === 'admins' && ($role === 'admin' || $role === 'administrator')) {
            $shouldSee = true;
        } elseif ($targetAudience === 'specific') {
            $targetUserIds = $announcement->target_user_ids ?? [];
            $shouldSee = in_array($user->id, $targetUserIds);
        }

        if (!$shouldSee) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Announcement not found for this user',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 404);
        }

        if (!$user->announcements()->where('announcements.id', $id)->exists()) {
            $user->announcements()->attach($id, [
                'is_read' => false,
                'read_at' => null,
            ]);
        } else {
            $user->announcements()->updateExistingPivot($id, [
                'is_read' => false,
                'read_at' => null,
            ]);
        }

        return response()->json([
            'status' => 'S',
            'message' => 'Announcement marked as unread',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Web Service API: Get announcement information by ID
     * This endpoint is designed for inter-module communication
     * Used by other modules to query announcement details
     * 
     * IFA Standard Compliance:
     * - Request must include timestamp or requestID (mandatory)
     * - Response includes status and timestamp (mandatory)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnnouncementInfo(Request $request)
    {
        // IFA Standard: Validate mandatory fields (timestamp or requestID)
        if (!$request->has('timestamp') && !$request->has('requestID')) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation error: timestamp or requestID is mandatory',
                'errors' => [
                    'timestamp' => 'Either timestamp or requestID must be provided',
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $request->validate([
            'announcement_id' => 'required|exists:announcements,id',
        ]);

        $announcement = Announcement::with(['creator', 'users'])
            ->findOrFail($request->announcement_id);

        // IFA Standard Response Format
        return response()->json([
            'status' => 'S', // S: Success, F: Fail, E: Error (IFA Standard)
            'message' => 'Announcement information retrieved successfully',
            'data' => [
                'announcement' => $announcement,
                'target_audience' => $announcement->target_audience,
                'target_user_ids' => $announcement->target_user_ids,
                'is_active' => $announcement->is_active,
                'published_at' => $announcement->published_at,
                'expires_at' => $announcement->expires_at,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
        ]);
    }

    /**
     * Web Service API: Get announcement IDs by criteria
     * This endpoint is designed for inter-module communication
     * Used by other modules to query announcements by various criteria
     * 
     * IFA Standard Compliance:
     * - Request must include timestamp or requestID (mandatory)
     * - Response includes status and timestamp (mandatory)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnnouncementIds(Request $request)
    {
        // IFA Standard: Validate mandatory fields (timestamp or requestID)
        if (!$request->has('timestamp') && !$request->has('requestID')) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation error: timestamp or requestID is mandatory',
                'errors' => [
                    'timestamp' => 'Either timestamp or requestID must be provided',
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $query = Announcement::query();

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
            }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by target_audience
        if ($request->has('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }

        // Filter by specific announcement IDs
        if ($request->has('announcement_ids') && is_array($request->announcement_ids)) {
            $query->whereIn('id', $request->announcement_ids);
        }

        // Filter by published status
        if ($request->has('published')) {
            if ($request->published) {
                $query->whereNotNull('published_at');
            } else {
                $query->whereNull('published_at');
            }
        }

        // Filter by expiration
        if ($request->has('expired')) {
            if ($request->expired) {
                $query->whereNotNull('expires_at')
                      ->where('expires_at', '<=', now());
            } else {
                $query->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });
    }
        }

        // Get only IDs
        $announcementIds = $query->pluck('id')->toArray();

        // IFA Standard Response Format
        return response()->json([
            'status' => 'S', // S: Success, F: Fail, E: Error (IFA Standard)
            'message' => 'Announcement IDs retrieved successfully',
            'data' => [
                'announcement_ids' => $announcementIds,
                'count' => count($announcementIds),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
        ]);
        }

    /**
     * Get target users based on announcement audience
     * This method uses UserWebService to call User Management Module's Web Service
     * instead of directly querying the database (Inter-module communication)
     */
    private function getTargetUsers(string $targetAudience, ?array $targetUserIds = null): array
    {
        try {
            // Prepare criteria based on target audience
            $criteria = [
                'status' => 'active',
            ];

            switch ($targetAudience) {
                case 'all':
                    // Get all active users
                    break;

                case 'students':
                case 'staff':
                    // Staff and students have same privilege, include both
                    $studentResult = $this->userWebService->getUserIds(['status' => 'active', 'role' => 'student']);
                    $staffResult = $this->userWebService->getUserIds(['status' => 'active', 'role' => 'staff']);
                    $allUserIds = array_unique(array_merge(
                        $studentResult['user_ids'] ?? [],
                        $staffResult['user_ids'] ?? []
                    ));
                    return $allUserIds;

                case 'admins':
                    $criteria['role'] = 'admin';
                    break;

                case 'specific':
                    // For specific users, use the provided user IDs
                    if (empty($targetUserIds)) {
                        return [];
                    }
                    $criteria['user_ids'] = $targetUserIds;
                    break;

                default:
                    return [];
            }

            // Use UserWebService to get user IDs via Web Service
            $result = $this->userWebService->getUserIds($criteria);
            
            return $result['user_ids'] ?? [];
            
        } catch (\Exception $e) {
            // Log error with announcement context
            Log::error('Failed to get target users for announcement via UserWebService', [
                'target_audience' => $targetAudience,
                'error' => $e->getMessage(),
            ]);
            
            throw new \Exception("Unable to retrieve target users. The user service is currently unavailable. Please try again later.");
        }
    }

}
