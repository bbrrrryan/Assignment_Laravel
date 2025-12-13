<?php
/**
 * Author: Liew Zi Li
 * Module: Notification Management Module
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications (Admin only)
     */
    public function index(Request $request)
    {
        $notifications = Notification::with('creator')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'data' => $notifications,
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

        $notification = Notification::create($validated + [
            'created_by' => auth()->id(),
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Notification created successfully',
            'data' => $notification->load('creator'),
        ], 201);
    }

    /**
     * Display the specified notification
     */
    public function show(string $id)
    {
        $notification = Notification::with(['creator', 'users'])->findOrFail($id);

        return response()->json([
            'message' => 'Notification retrieved successfully',
            'data' => $notification,
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
            'message' => 'Notification updated successfully',
            'data' => $notification->load('creator'),
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
            'message' => 'Notification deleted successfully',
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
                'message' => 'Notification is not active',
            ], 400);
        }

        // Determine target users based on audience
        $targetUsers = $this->getTargetUsers($notification);

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
            'message' => 'Notification sent successfully',
            'data' => [
                'notification' => $notification,
                'recipients_count' => count($targetUsers),
            ],
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
            'message' => 'My notifications retrieved successfully',
            'data' => $notifications,
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
            'message' => 'Unread notifications count retrieved successfully',
            'data' => [
                'count' => $count,
            ],
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
                'message' => 'Notification not found for this user',
            ], 404);
        }

        $user->notifications()->updateExistingPivot($id, [
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read',
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
                'message' => 'Notification not found for this user',
            ], 404);
        }

        $user->notifications()->updateExistingPivot($id, [
            'is_read' => false,
            'read_at' => null,
        ]);

        return response()->json([
            'message' => 'Notification marked as unread',
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
                'message' => 'Notification not found for this user',
            ], 404);
        }

        $user->notifications()->updateExistingPivot($id, [
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification acknowledged',
        ]);
    }

    /**
     * Get user's announcements and notifications for dropdown (combined)
     * Returns recent items from announcements and notifications tables directly
     */
    public function getUnreadItems(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);
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
                'created_at' => $announcement->created_at ? $announcement->created_at->toDateTimeString() : null,
                'pivot_created_at' => $pivotCreatedAt,
                'creator' => $announcement->creator ? $announcement->creator->name : 'System',
                'is_read' => $pivotData ? (bool)($pivotData->is_read ?? false) : false,
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
                    'created_at' => $notification->created_at ? $notification->created_at->toDateTimeString() : null,
                    'pivot_created_at' => $pivotCreatedAt,
                    'creator' => $notification->creator ? $notification->creator->name : 'System',
                    'is_read' => (bool)($notification->pivot->is_read ?? false),
                ];
            })
            ->filter(function($item) use ($onlyUnread) {
                if ($onlyUnread) {
                    return !$item['is_read'];
                }
                return true;
            });

        // Combine and sort by created_at (unread items first)
        $combined = $announcements->concat($notifications);
        
        $items = $combined->sort(function ($a, $b) {
                // Unread items first
                if ($a['is_read'] !== $b['is_read']) {
                    return $a['is_read'] ? 1 : -1;
                }
                // Then sort by date (most recent first)
                $dateA = $a['pivot_created_at'] ?? $a['created_at'] ?? '';
                $dateB = $b['pivot_created_at'] ?? $b['created_at'] ?? '';
                
                // Convert to timestamp for comparison
                $timestampA = is_string($dateA) ? strtotime($dateA) : (is_object($dateA) ? $dateA->timestamp : 0);
                $timestampB = is_string($dateB) ? strtotime($dateB) : (is_object($dateB) ? $dateB->timestamp : 0);
                
                return $timestampB <=> $timestampA;
            })
            ->take($limit)
            ->values();

        // Get total unread counts for badge (count unread items)
        $announcementCount = $announcements->filter(function($item) {
            return !$item['is_read'];
        })->count();

        $notificationCount = $notifications->filter(function($item) {
            return !$item['is_read'];
        })->count();
        
        \Log::info('User ' . $user->id . ' items count - announcements: ' . $announcements->count() . ', notifications: ' . $notifications->count() . ', total items: ' . $items->count());
        \Log::info('Filtered announcements count: ' . $filteredAnnouncements->count());
        \Log::info('All announcements count: ' . $allAnnouncements->count());

        return response()->json([
            'message' => 'Items retrieved successfully',
            'data' => [
                'items' => $items->toArray(), // Ensure it's an array
                'counts' => [
                    'announcements' => $announcementCount,
                    'notifications' => $notificationCount,
                    'total' => $announcementCount + $notificationCount,
                ],
                'debug' => [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'announcements_total' => $allAnnouncements->count(),
                    'announcements_filtered' => $filteredAnnouncements->count(),
                    'announcements_mapped' => $announcements->count(),
                    'notifications_count' => $notifications->count(),
                    'combined_count' => $combined->count(),
                    'items_count' => $items->count(),
                ],
            ],
        ]);
    }

    /**
     * Get target users based on notification audience
     */
    private function getTargetUsers(Notification $notification): array
    {
        switch ($notification->target_audience) {
            case 'all':
                return User::where('status', 'active')->pluck('id')->toArray();

            case 'students':
                return User::where('status', 'active')
                    ->where('role', 'student')
                    ->pluck('id')->toArray();

            case 'staff':
                return User::where('status', 'active')
                    ->where('role', 'staff')
                    ->pluck('id')->toArray();

            case 'admins':
                return User::where('status', 'active')
                    ->where('role', 'admin')
                    ->pluck('id')->toArray();

            case 'specific':
                return $notification->target_user_ids ?? [];

            default:
                return [];
        }
    }
}
