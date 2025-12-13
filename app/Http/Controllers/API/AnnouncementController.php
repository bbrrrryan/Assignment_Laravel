<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements (Admin only)
     */
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
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort_by to prevent SQL injection
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
            'message' => 'Announcements retrieved successfully',
            'data' => $announcements,
        ]);
    }

    /**
     * Store a newly created announcement
     */
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
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement = Announcement::create($validated + [
            'created_by' => auth()->id(),
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Announcement created successfully',
            'data' => $announcement->load('creator'),
        ], 201);
    }

    /**
     * Display the specified announcement
     */
    public function show(string $id)
    {
        $announcement = Announcement::with(['creator', 'users'])->findOrFail($id);

        // Increment views count
        $announcement->increment('views_count');

        return response()->json([
            'message' => 'Announcement retrieved successfully',
            'data' => $announcement,
        ]);
    }

    /**
     * Update the specified announcement
     */
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
            'message' => 'Announcement updated successfully',
            'data' => $announcement->load('creator'),
        ]);
    }

    /**
     * Remove the specified announcement
     */
    public function destroy(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'message' => 'Announcement deleted successfully',
        ]);
    }

    /**
     * Publish announcement to target users
     */
    public function publish(string $id)
    {
        $announcement = Announcement::findOrFail($id);

        if (!$announcement->is_active) {
            return response()->json([
                'message' => 'Announcement is not active',
            ], 400);
        }

        // Determine target users based on audience
        $targetUsers = $this->getTargetUsers($announcement);

        // Attach announcement to users
        $syncData = [];
        foreach ($targetUsers as $userId) {
            $syncData[$userId] = [
                'is_read' => false,
            ];
        }

        $announcement->users()->sync($syncData);

        // Update published_at if not set
        if (!$announcement->published_at) {
            $announcement->update(['published_at' => now()]);
        }

        return response()->json([
            'message' => 'Announcement published successfully',
            'data' => [
                'announcement' => $announcement,
                'recipients_count' => count($targetUsers),
            ],
        ]);
    }

    /**
     * Get unread announcements count for current user
     */
    public function unreadCount()
    {
        $user = auth()->user();
        $count = $user->announcements()
            ->where('is_active', true)
            ->wherePivot('is_read', false)
            ->count();

        return response()->json([
            'message' => 'Unread announcements count retrieved successfully',
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get current user's announcements
     */
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
            'message' => 'My announcements retrieved successfully',
            'data' => $announcements,
        ]);
    }

    /**
     * Mark announcement as read
     */
    public function markAsRead(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        if (!$user->announcements()->where('announcements.id', $id)->exists()) {
            return response()->json([
                'message' => 'Announcement not found for this user',
            ], 404);
        }

        $user->announcements()->updateExistingPivot($id, [
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Announcement marked as read',
        ]);
    }

    /**
     * Mark announcement as unread
     */
    public function markAsUnread(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        // Check if announcement should be visible to this user (same logic as getUnreadItems)
        if (!$announcement->is_active || !$announcement->published_at) {
            return response()->json([
                'message' => 'Announcement is not published',
            ], 404);
        }

        // Check if announcement has expired
        if ($announcement->expires_at && $announcement->expires_at->isPast()) {
            return response()->json([
                'message' => 'Announcement has expired',
            ], 404);
        }

        // Check if user should see this announcement (same logic as getUnreadItems)
        $targetAudience = $announcement->target_audience;
        $role = strtolower($user->role ?? '');
        $shouldSee = false;

        if ($targetAudience === 'all') {
            $shouldSee = true;
        } elseif ($targetAudience === 'students' && $role === 'student') {
            $shouldSee = true;
        } elseif ($targetAudience === 'staff' && $role === 'staff') {
            $shouldSee = true;
        } elseif ($targetAudience === 'admins' && ($role === 'admin' || $role === 'administrator')) {
            $shouldSee = true;
        } elseif ($targetAudience === 'specific') {
            $targetUserIds = $announcement->target_user_ids ?? [];
            $shouldSee = in_array($user->id, $targetUserIds);
        }

        if (!$shouldSee) {
            return response()->json([
                'message' => 'Announcement not found for this user',
            ], 404);
        }

        // Check if announcement exists in pivot table
        if (!$user->announcements()->where('announcements.id', $id)->exists()) {
            // Create the pivot record if it doesn't exist
            $user->announcements()->attach($id, [
                'is_read' => false,
                'read_at' => null,
            ]);
        } else {
            // Update existing pivot record
            $user->announcements()->updateExistingPivot($id, [
                'is_read' => false,
                'read_at' => null,
            ]);
        }

        return response()->json([
            'message' => 'Announcement marked as unread',
        ]);
    }

    /**
     * Get target users based on announcement audience
     */
    private function getTargetUsers(Announcement $announcement): array
    {
        switch ($announcement->target_audience) {
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
                return $announcement->target_user_ids ?? [];

            default:
                return [];
        }
    }
}
