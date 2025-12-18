<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Factories\AnnouncementFactory;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
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
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement = AnnouncementFactory::makeAnnouncement(
            $validated['type'],
            $validated['title'],
            $validated['content'],
            $validated['target_audience'],
            auth()->id(),
            $validated['priority'] ?? null,
            $validated['target_user_ids'] ?? null,
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

        $targetUsers = $this->getTargetUsers($announcement);

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

    private function getTargetUsers(Announcement $announcement): array
    {
        try {
            $baseUrl = config('app.url', 'http://localhost:8000');
            $apiUrl = rtrim($baseUrl, '/') . '/api/users/service/get-ids';

            $params = [
                'status' => 'active',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            switch ($announcement->target_audience) {
                case 'all':
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
                    $targetUserIds = $announcement->target_user_ids ?? [];
                    if (empty($targetUserIds)) {
                        return [];
                    }
                    $params['user_ids'] = $targetUserIds;
                    break;

                default:
                    return [];
            }

            $response = Http::timeout(10)->post($apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['user_ids']) && is_array($data['data']['user_ids'])) {
                    return $data['data']['user_ids'];
                }
            } else {
                Log::warning('Failed to get user IDs from User Management Module', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'announcement_id' => $announcement->id,
                ]);
                
                return $this->getTargetUsersFallback($announcement);
            }
        } catch (\Exception $e) {
            Log::error('Exception when calling User Management Module', [
                'message' => $e->getMessage(),
                'announcement_id' => $announcement->id,
            ]);
            
            return $this->getTargetUsersFallback($announcement);
        }

        return [];
    }

    private function getTargetUsersFallback(Announcement $announcement): array
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
