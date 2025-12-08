<?php
/**
 * Author: Liew Zi Li
 * Module: Notification Management Module
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
            ->where('is_active', true)
            ->when($request->is_read !== null, function($q) use ($request) {
                $q->wherePivot('is_read', $request->is_read);
            })
            ->when($request->type, fn($q) => $q->where('type', $request->type));

        $notifications = $query->latest('pivot_created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'message' => 'My notifications retrieved successfully',
            'data' => $notifications,
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
     * Get target users based on notification audience
     */
    private function getTargetUsers(Notification $notification): array
    {
        switch ($notification->target_audience) {
            case 'all':
                return User::where('status', 'active')->pluck('id')->toArray();

            case 'students':
                return User::where('status', 'active')
                    ->whereHas('role', fn($q) => $q->where('name', 'student'))
                    ->pluck('id')->toArray();

            case 'staff':
                return User::where('status', 'active')
                    ->whereHas('role', fn($q) => $q->where('name', 'staff'))
                    ->pluck('id')->toArray();

            case 'admins':
                return User::where('status', 'active')
                    ->whereHas('role', fn($q) => $q->where('name', 'admin'))
                    ->pluck('id')->toArray();

            case 'specific':
                return $notification->target_user_ids ?? [];

            default:
                return [];
        }
    }
}
