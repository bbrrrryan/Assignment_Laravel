<?php
/**
 * Author: Liew Zi Li
 * Module: Notification Management Module
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Factories\NotificationFactory;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications (Admin only)
     */
    public function index(Request $request)
    {
        $notifications = Notification::with('creator')
            ->when($request->search, function($q) use ($request) {
                $search = $request->search;
                $q->where(function($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                          ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'S',
            'message' => 'Notifications retrieved successfully',
            'data' => $notifications,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,error,reminder',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'target_audience' => 'required|in:all,students,staff,admins,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'scheduled_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'is_active' => 'nullable|boolean',
        ]);

        $notification = NotificationFactory::makeNotification(
            $validated['type'],
            $validated['title'],
            $validated['message'],
            $validated['target_audience'],
            auth()->id(),
            $validated['priority'] ?? null,
            $validated['target_user_ids'] ?? null,
            $validated['scheduled_at'] ?? null,
            $validated['expires_at'] ?? null,
            $request->is_active ?? true
        );

        return response()->json([
            'status' => 'S',
            'message' => 'Notification created successfully',
            'data' => $notification->load('creator'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

    /**
     * Display the specified notification
     */
    public function show(string $id)
    {
        $notification = Notification::with(['creator', 'users'])->findOrFail($id);

        return response()->json([
            'status' => 'S',
            'message' => 'Notification retrieved successfully',
            'data' => $notification,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update the specified notification
     */
    public function update(Request $request, string $id)
    {
        $notification = Notification::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:info,warning,success,error,reminder',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'target_audience' => 'sometimes|required|in:all,students,staff,admins,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'scheduled_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'is_active' => 'nullable|boolean',
        ]);

        $notification->update($validated);

        return response()->json([
            'status' => 'S',
            'message' => 'Notification updated successfully',
            'data' => $notification->load('creator'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove the specified notification
     */
    public function destroy(string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'status' => 'S',
            'message' => 'Notification deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Send notification to target users
     */
    public function send(string $id)
    {
        $notification = Notification::findOrFail($id);

        if (!$notification->is_active) {
            return response()->json([
                'status' => 'F',
                'message' => 'Notification is not active',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 400);
        }

        try {
            // Determine target users based on audience
            $targetUsers = $this->getTargetUsers($notification);
        } catch (\Exception $e) {
            Log::error('Failed to get target users for notification', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => 'F',
                'message' => 'Unable to retrieve target users. The user service is currently unavailable. Please try again later.',
                'error_details' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 503);
        }

        // Attach notification to users
        $syncData = [];
        foreach ($targetUsers as $userId) {
            $syncData[$userId] = [
                'is_read' => false,
                'is_acknowledged' => false,
            ];
        }

        $notification->users()->sync($syncData);

        // Update scheduled_at if not set
        if (!$notification->scheduled_at) {
            $notification->update(['scheduled_at' => now()]);
        }

        return response()->json([
            'status' => 'S',
            'message' => 'Notification sent successfully',
            'data' => [
                'notification' => $notification,
                'recipients_count' => count($targetUsers),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get current user's notifications
     */
    public function myNotifications(Request $request)
    {
        $user = auth()->user();

        $query = $user->notifications()
            ->with('creator')
            ->where('is_active', true)
            ->when($request->is_read !== null, function($q) use ($request) {
                $q->wherePivot('is_read', $request->is_read);
            })
            ->when($request->type, fn($q) => $q->where('type', $request->type));

        // Order by notification created_at (when notification was created)
        // or pivot created_at (when notification was sent to user)
        $notifications = $query->orderBy('notifications.created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'S',
            'message' => 'My notifications retrieved successfully',
            'data' => $notifications,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get unread notifications count for current user
     */
    public function unreadCount()
    {
        $user = auth()->user();
        $count = $user->notifications()
            ->where('is_active', true)
            ->wherePivot('is_read', false)
            ->count();

        return response()->json([
            'status' => 'S',
            'message' => 'Unread notifications count retrieved successfully',
            'data' => [
                'count' => $count,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $id)
    {
        $notification = Notification::findOrFail($id);
        $user = auth()->user();

        if (!$user->notifications()->where('notifications.id', $id)->exists()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Notification not found for this user',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        $user->notifications()->updateExistingPivot($id, [
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'status' => 'S',
            'message' => 'Notification marked as read',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(string $id)
    {
        $notification = Notification::findOrFail($id);
        $user = auth()->user();

        if (!$user->notifications()->where('notifications.id', $id)->exists()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Notification not found for this user',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        $user->notifications()->updateExistingPivot($id, [
            'is_read' => false,
            'read_at' => null,
        ]);

        return response()->json([
            'status' => 'S',
            'message' => 'Notification marked as unread',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Acknowledge notification
     */
    public function acknowledge(string $id)
    {
        $notification = Notification::findOrFail($id);
        $user = auth()->user();

        if (!$user->notifications()->where('notifications.id', $id)->exists()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Notification not found for this user',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        $user->notifications()->updateExistingPivot($id, [
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
        ]);

        return response()->json([
            'status' => 'S',
            'message' => 'Notification acknowledged',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get user's announcements and notifications for dropdown (combined)
     * Returns recent items from announcements and notifications tables directly
     */
    public function getUnreadItems(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        // Handle both string and boolean values for only_unread
        $onlyUnreadParam = $request->get('only_unread', false);
        $onlyUnread = filter_var($onlyUnreadParam, FILTER_VALIDATE_BOOLEAN);
        
        // Get announcements - Get all published announcements that match user's audience
        // Then filter by checking if announcement should be visible to this user
        $announcementQuery = Announcement::with('creator')
            ->where('is_active', true)
            ->whereNotNull('published_at') // Only published announcements
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
        
        $allAnnouncements = $announcementQuery->get();
        
        // Filter announcements based on target_audience
        $filteredAnnouncements = $allAnnouncements->filter(function($announcement) use ($user) {
            $targetAudience = $announcement->target_audience;
            $role = strtolower($user->role ?? '');
            
            // Check if announcement should be visible to this user
            if ($targetAudience === 'all') {
                return true;
            } elseif ($targetAudience === 'students' && $role === 'student') {
                return true;
            } elseif ($targetAudience === 'staff' && $role === 'staff') {
                return true;
            } elseif ($targetAudience === 'admins' && ($role === 'admin' || $role === 'administrator')) {
                return true;
            } elseif ($targetAudience === 'specific') {
                $targetUserIds = $announcement->target_user_ids ?? [];
                return in_array($user->id, $targetUserIds);
            }
            
            return false;
        });
        
        // Get read status from pivot table for each announcement
        $announcements = $filteredAnnouncements->map(function ($announcement) use ($user) {
            // Get pivot data using DB query
            $pivotData = DB::table('user_announcement')
                ->where('user_id', $user->id)
                ->where('announcement_id', $announcement->id)
                ->first();
            
            $pivotCreatedAt = $pivotData ? $pivotData->created_at : ($announcement->published_at ?? $announcement->created_at);
            // Convert to string if it's a Carbon instance
            if ($pivotCreatedAt instanceof \Carbon\Carbon) {
                $pivotCreatedAt = $pivotCreatedAt->toDateTimeString();
            }
            
            return [
                'id' => $announcement->id,
                'type' => 'announcement',
                'title' => $announcement->title,
                'content' => $announcement->content,
                'created_at' => $announcement->created_at ? $announcement->created_at->toIso8601String() : null,
                'pivot_created_at' => $pivotCreatedAt,
                'creator' => $announcement->creator ? $announcement->creator->name : 'System',
                'is_read' => $pivotData ? (bool)($pivotData->is_read ?? false) : false,
                'is_starred' => $pivotData ? (bool)($pivotData->is_starred ?? false) : false,
            ];
        })->filter(function($item) use ($onlyUnread) {
            if ($onlyUnread) {
                return !$item['is_read'];
            }
            return true;
        });

        // Get notifications - ONLY notifications that are sent to this specific user
        // Use the user_notification pivot table to ensure we only get notifications for this user
        $notifications = $user->notifications()
            ->with('creator')
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get()
            ->map(function ($notification) {
                $pivotCreatedAt = $notification->pivot->created_at ?? ($notification->scheduled_at ?? $notification->created_at);
                // Convert to string if it's a Carbon instance
                if ($pivotCreatedAt instanceof \Carbon\Carbon) {
                    $pivotCreatedAt = $pivotCreatedAt->toDateTimeString();
                }
                
                return [
                    'id' => $notification->id,
                    'type' => 'notification',
                    'title' => $notification->title,
                    'content' => $notification->message,
                    'created_at' => $notification->created_at ? $notification->created_at->toIso8601String() : null,
                    'pivot_created_at' => $pivotCreatedAt,
                    'creator' => $notification->creator ? $notification->creator->name : 'System',
                    'is_read' => (bool)($notification->pivot->is_read ?? false),
                    'is_starred' => (bool)($notification->pivot->is_starred ?? false),
                ];
            })
            ->filter(function($item) use ($onlyUnread) {
                if ($onlyUnread) {
                    return !$item['is_read'];
                }
                return true;
            });

        // Combine and sort by created_at (unread items first, then starred, then by date)
        $combined = $announcements->concat($notifications);
        
        // apply search filter if search term provided
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $combined = $combined->filter(function($item) use ($searchLower) {
                $title = strtolower($item['title'] ?? '');
                $content = strtolower($item['content'] ?? '');
                return strpos($title, $searchLower) !== false || strpos($content, $searchLower) !== false;
            });
        }
        
        $sorted = $combined->sort(function ($a, $b) {
                // Unread items first
                if ($a['is_read'] !== $b['is_read']) {
                    return $a['is_read'] ? 1 : -1;
                }
                // Then starred items
                if ($a['is_starred'] !== $b['is_starred']) {
                    return $a['is_starred'] ? -1 : 1;
                }
                // Then sort by date (most recent first)
                $dateA = $a['pivot_created_at'] ?? $a['created_at'] ?? '';
                $dateB = $b['pivot_created_at'] ?? $b['created_at'] ?? '';
                
                // Convert to timestamp for comparison
                $timestampA = is_string($dateA) ? strtotime($dateA) : (is_object($dateA) ? $dateA->timestamp : 0);
                $timestampB = is_string($dateB) ? strtotime($dateB) : (is_object($dateB) ? $dateB->timestamp : 0);
                
                return $timestampB <=> $timestampA;
            })
            ->values();

        // Manual pagination using LengthAwarePaginator
        $total = $sorted->count();
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();
        
        // Create pagination data structure similar to Laravel's paginator
        $lastPage = (int) ceil($total / $perPage);
        $paginationData = [
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $total > 0 ? $offset + 1 : null,
            'to' => min($offset + $perPage, $total),
        ];

        // Get total unread counts for badge (count unread items)
        $announcementCount = $announcements->filter(function($item) {
            return !$item['is_read'];
        })->count();

        $notificationCount = $notifications->filter(function($item) {
            return !$item['is_read'];
        })->count();

        return response()->json([
            'status' => 'S',
            'message' => 'Items retrieved successfully',
            'data' => [
                'items' => $items->toArray(),
                'pagination' => $paginationData,
                'counts' => [
                    'announcements' => $announcementCount,
                    'notifications' => $notificationCount,
                    'total' => $announcementCount + $notificationCount,
                ],
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get target users based on notification audience
     * This method uses HTTP to call User Management Module's Web Service
     * instead of directly querying the database (Inter-module communication)
     */
    private function getTargetUsers(Notification $notification): array
    {
        try {
            // Get base URL for User Management Module
            $baseUrl = config('app.url', 'http://localhost:8000');
            $apiUrl = rtrim($baseUrl, '/') . '/api/users/service/get-ids';

            // Prepare request parameters based on target audience
            // IFA Standard: Include timestamp in request (mandatory requirement)
            $params = [
                'status' => 'active',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
            ];

            switch ($notification->target_audience) {
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
                    $targetUserIds = $notification->target_user_ids ?? [];
                    if (empty($targetUserIds)) {
                        return [];
                    }
                    $params['user_ids'] = $targetUserIds;
                    break;

                default:
                    return [];
            }

            // Make HTTP request to User Management Module (Inter-module Web Service call)
            $response = Http::timeout(10)->post($apiUrl, $params);

            if (!$response->successful()) {
                // HTTP request failed
                Log::error('Failed to get user IDs from User Management Module', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'notification_id' => $notification->id,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "User Web Service unavailable. HTTP Status: {$response->status()}. " .
                    "Response: {$response->body()}"
                );
            }
            
            $data = $response->json();
            
            if (!isset($data['data']['user_ids']) || !is_array($data['data']['user_ids'])) {
                Log::error('User Web Service returned invalid response', [
                    'notification_id' => $notification->id,
                    'response' => $data,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service returned invalid response format");
            }
            
            return $data['data']['user_ids'];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('User Web Service connection exception', [
                'notification_id' => $notification->id,
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
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'url' => $apiUrl ?? 'unknown',
            ]);
            
            throw new \Exception("User Web Service error: {$e->getMessage()}");
        }
    }

    /**
     * Star/Unstar a notification or announcement
     */
    public function toggleStar(Request $request, string $type, string $id)
    {
        $user = auth()->user();
        
        if (!in_array($type, ['notification', 'announcement'])) {
            return response()->json([
                'status' => 'F',
                'message' => 'Invalid type. Must be "notification" or "announcement"',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 400);
        }

        if ($type === 'notification') {
            $notification = Notification::findOrFail($id);
            $pivot = DB::table('user_notification')
                ->where('user_id', $user->id)
                ->where('notification_id', $id)
                ->first();

            if (!$pivot) {
                return response()->json([
                    'status' => 'F',
                    'message' => 'Notification not found or not assigned to user',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 404);
            }

            $isStarred = !$pivot->is_starred;
            
            DB::table('user_notification')
                ->where('user_id', $user->id)
                ->where('notification_id', $id)
                ->update([
                    'is_starred' => $isStarred,
                    'starred_at' => $isStarred ? now() : null,
                ]);

            return response()->json([
                'status' => 'S',
                'message' => $isStarred ? 'Notification starred successfully' : 'Notification unstarred successfully',
                'data' => [
                    'is_starred' => $isStarred,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        } else {
            // announcement
            $announcement = Announcement::findOrFail($id);
            $pivot = DB::table('user_announcement')
                ->where('user_id', $user->id)
                ->where('announcement_id', $id)
                ->first();

            // If pivot doesn't exist, create it
            if (!$pivot) {
                DB::table('user_announcement')->insert([
                    'user_id' => $user->id,
                    'announcement_id' => $id,
                    'is_read' => false,
                    'is_starred' => true,
                    'starred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status' => 'S',
                    'message' => 'Announcement starred successfully',
                    'data' => [
                        'is_starred' => true,
                    ],
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ]);
            }

            $isStarred = !$pivot->is_starred;
            
            DB::table('user_announcement')
                ->where('user_id', $user->id)
                ->where('announcement_id', $id)
                ->update([
                    'is_starred' => $isStarred,
                    'starred_at' => $isStarred ? now() : null,
                ]);

            return response()->json([
                'status' => 'S',
                'message' => $isStarred ? 'Announcement starred successfully' : 'Announcement unstarred successfully',
                'data' => [
                    'is_starred' => $isStarred,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
