<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use App\Models\Reward;
use App\Models\Certificate;
use App\Models\User;
use App\Factories\LoyaltyFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class LoyaltyController extends Controller
{
    
    public function getPoints()
    {
        $points = auth()->user()->loyaltyPoints()->sum('points');
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => ['total_points' => $points],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function pointsHistory(Request $request)
    {
        $history = auth()->user()->loyaltyPoints()
            ->latest()
            ->paginate(15);
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $history,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function getRewards()
    {
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => Reward::where('is_active', true)->get(),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function redeemReward(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        $user = auth()->user();
        $reward = Reward::findOrFail($request->reward_id);

        if (!$reward->is_active) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'This reward is not available',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 400);
        }

        // For certificate-type rewards, each user can only redeem once (pending/approved/redeemed)
        if ($reward->reward_type === 'certificate') {
            $alreadyRedeemed = DB::table('user_reward')
                ->where('user_id', $user->id)
                ->where('reward_id', $reward->id)
                ->whereIn('status', ['pending', 'approved', 'redeemed'])
                ->exists();

            if ($alreadyRedeemed) {
                return response()->json([
                    'status' => 'F', // IFA Standard: F (Fail)
                    'message' => 'You have already redeemed this certificate reward.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                ], 400);
            }
        }

        $totalPoints = $user->loyaltyPoints()->sum('points');
        if ($totalPoints < $reward->points_required) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Insufficient points. Required: ' . $reward->points_required . ', Available: ' . $totalPoints,
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 400);
        }

        if ($reward->stock_quantity !== null && $reward->stock_quantity <= 0) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'This reward is out of stock',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 400);
        }

        DB::beginTransaction();
        try {
            $user->loyaltyPoints()->create([
                'points' => -$reward->points_required,
                'action_type' => 'reward_redemption',
                'description' => "Redeemed reward: {$reward->name}",
            ]);

            $user->rewards()->attach($reward->id, [
                'points_used' => $reward->points_required,
                'status' => 'pending',
                'redeemed_at' => now(),
            ]);

            if ($reward->stock_quantity !== null) {
                $reward->decrement('stock_quantity');
            }

            DB::commit();

            return response()->json([
                'status' => 'S', // IFA Standard
                'message' => 'Reward redeemed successfully. Awaiting approval.',
                'data' => [
                    'reward' => $reward,
                    'points_used' => $reward->points_required,
                    'remaining_points' => $totalPoints - $reward->points_required,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'E', // IFA Standard: E (Error)
                'message' => 'Failed to redeem reward',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 500);
        }
    }

    public function getCertificates()
    {
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => auth()->user()->certificates,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get current user's redeemed rewards (badges, certificates, privileges, etc.)
     */
    public function myRewards(Request $request)
    {
        $user = auth()->user();

        $query = $user->rewards()
            ->withPivot('points_used', 'status', 'approved_by', 'redeemed_at', 'created_at')
            ->orderBy('user_reward.created_at', 'desc');

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->wherePivot('status', $request->status);
        }

        $rewards = $query->get()->map(function ($reward) {
            return [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'points_required' => $reward->points_required,
                'reward_type' => $reward->reward_type,
                'image_url' => $reward->image_url,
                'points_used' => $reward->pivot->points_used,
                'status' => $reward->pivot->status,
                'approved_by' => $reward->pivot->approved_by,
                'redeemed_at' => $reward->pivot->redeemed_at,
                'created_at' => $reward->pivot->created_at,
            ];
        });

        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $rewards,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    // ==================== ADMIN METHODS ====================

    /**
     * Award points to a user (Admin only)
     */
    public function awardPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1',
            'action_type' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $point = LoyaltyFactory::makeLoyaltyPoint(
            $request->user_id,
            $request->points,
            $request->action_type,
            $request->description
        );

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Points awarded successfully',
            'data' => $point->load('user'),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    /**
     * Deduct points from a user (Admin only)
     */
    public function deductPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1',
            'action_type' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $point = LoyaltyFactory::makeLoyaltyPoint(
            $request->user_id,
            -abs($request->points),
            $request->action_type,
            $request->description
        );

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Points deducted successfully',
            'data' => $point->load('user'),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    /**
     * Get all users' points (Admin only)
     * Only shows students, excludes staff and admin users
     */
    public function getAllUsersPoints(Request $request)
    {
        // ✅ Service Consumption: Get user IDs via HTTP from User Management Module
        try {
            $baseUrl = config('app.url', 'http://localhost:8000');
            $apiUrl = rtrim($baseUrl, '/') . '/api/users/service/get-ids';
            
            $userResponse = Http::timeout(10)->post($apiUrl, [
                'role' => 'student',
                'status' => 'active',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ]);
            
            if ($userResponse->successful()) {
                $userData = $userResponse->json();
                if ($userData['status'] === 'S' && isset($userData['data']['user_ids'])) {
                    $userIds = $userData['data']['user_ids'];
                    // Use the user IDs to query loyalty points
                    $query = User::with('loyaltyPoints')
                        ->whereIn('id', $userIds);
                } else {
                    // Fallback to direct query
                    $query = User::with('loyaltyPoints')
                        ->where('role', 'student');
                }
            } else {
                // Fallback to direct query
                Log::warning('Failed to get user IDs from User Management Module', [
                    'status' => $userResponse->status(),
                ]);
                $query = User::with('loyaltyPoints')
                    ->where('role', 'student');
            }
        } catch (\Exception $e) {
            // Fallback to direct query
            Log::error('Exception when calling User Management Module: ' . $e->getMessage());
            $query = User::with('loyaltyPoints')
                ->where('role', 'student');
        }
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('personal_id', 'like', "%{$search}%");
            });
        }

        $users = $query->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'personal_id' => $user->personal_id ?? null,
                'total_points' => $user->loyaltyPoints()->sum('points'),
                'points_count' => $user->loyaltyPoints()->count(),
            ];
        })->sortByDesc('total_points')->values();

        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $users,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get specific user's points (Admin only)
     */
    public function getUserPoints($userId)
    {
        $user = User::with('loyaltyPoints')->findOrFail($userId);
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => [
                'user' => $user,
                'total_points' => $user->loyaltyPoints()->sum('points'),
                'points_history' => $user->loyaltyPoints()->latest()->paginate(15),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get all loyalty rules (Admin only)
     */
    public function getRules()
    {
        $rules = LoyaltyRule::latest()->get();
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $rules,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Create loyalty rule (Admin only)
     */
    public function createRule(Request $request)
    {
        $validated = $request->validate([
            'action_type' => 'required|string|max:255|unique:loyalty_rules,action_type',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'conditions' => 'nullable|array',
        ]);

        $rule = LoyaltyFactory::makeLoyaltyRule(
            $validated['action_type'],
            $validated['name'],
            $validated['points'],
            $validated['description'] ?? null,
            $validated['is_active'] ?? true,
            $validated['conditions'] ?? null
        );
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Rule created successfully',
            'data' => $rule,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    /**
     * Update loyalty rule (Admin only)
     */
    public function updateRule(Request $request, $id)
    {
        $rule = LoyaltyRule::findOrFail($id);
        
        $validated = $request->validate([
            'action_type' => 'required|string|max:255|unique:loyalty_rules,action_type,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'conditions' => 'nullable|array',
        ]);

        $rule->update($validated);
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Rule updated successfully',
            'data' => $rule,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Delete loyalty rule (Admin only)
     */
    public function deleteRule($id)
    {
        $rule = LoyaltyRule::findOrFail($id);
        $rule->delete();
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Rule deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get all rewards (Admin only - includes inactive)
     */
    public function getAllRewards()
    {
        $rewards = Reward::latest()->get();
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $rewards,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Create reward (Admin only)
     */
    public function createReward(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:certificate,physical',
            'image_url' => 'nullable|string', // Can be base64 image string or URL
            'stock_quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Validate base64 image if provided
        if ($request->has('image_url') && $request->image_url) {
            // Check if it's a base64 image string
            if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $request->image_url)) {
                // Check base64 string length (limit to ~1.5MB base64, which is ~1MB actual image)
                if (strlen($request->image_url) > 1500000) {
                    return response()->json([
                        'status' => 'F', // IFA Standard: F (Fail)
                        'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.',
                        'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                    ], 422);
                }
            }
        }

        $reward = LoyaltyFactory::makeReward(
            $validated['name'],
            $validated['points_required'],
            $validated['reward_type'],
            $validated['description'] ?? null,
            $validated['image_url'] ?? null,
            $validated['stock_quantity'] ?? null,
            $validated['is_active'] ?? true
        );
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Reward created successfully',
            'data' => $reward,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    /**
     * Update reward (Admin only)
     */
    public function updateReward(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:certificate,physical',
            'image_url' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Validate base64 image if provided
        if ($request->has('image_url') && $request->image_url) {
            // Check if it's a base64 image string
            if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $request->image_url)) {
                // Check base64 string length (limit to ~1.5MB base64, which is ~1MB actual image)
                if (strlen($request->image_url) > 1500000) {
                    return response()->json([
                        'status' => 'F', // IFA Standard: F (Fail)
                        'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.',
                        'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                    ], 422);
                }
            }
        }

        $reward->update($validated);
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Reward updated successfully',
            'data' => $reward,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Delete reward (Admin only)
     */
    public function deleteReward($id)
    {
        $reward = Reward::findOrFail($id);
        $reward->delete();
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Reward deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get all redemptions (Admin only)
     */
    public function getRedemptions(Request $request)
    {
        $query = DB::table('user_reward')
            ->join('users', 'user_reward.user_id', '=', 'users.id')
            ->join('rewards', 'user_reward.reward_id', '=', 'rewards.id')
            ->select(
                'user_reward.*',
                'users.name as user_name',
                'users.email as user_email',
                'rewards.name as reward_name',
                'rewards.description as reward_description'
            );

        if ($request->has('status') && $request->status) {
            $query->where('user_reward.status', $request->status);
        }

        $redemptions = $query->latest('user_reward.created_at')->paginate(15);
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $redemptions,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Approve redemption (Admin only)
     */
    public function approveRedemption(Request $request, $id)
    {
        $redemption = DB::table('user_reward')->where('id', $id)->first();
        
        if (!$redemption) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Redemption not found',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Update redemption status
            DB::table('user_reward')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            // Automatically issue certificate if this is a certificate-type reward
            $reward = Reward::find($redemption->reward_id);
            if ($reward && $reward->reward_type === 'certificate') {
                // Optional: prevent duplicate certificates for the same reward/user on the same day
                $existing = Certificate::where('user_id', $redemption->user_id)
                    ->where('reward_id', $reward->id)
                    ->whereDate('issued_date', now()->toDateString())
                    ->first();

                if (!$existing) {
                    LoyaltyFactory::makeCertificate(
                        $redemption->user_id,
                        $reward->name,
                        $reward->id,
                        $reward->description,
                        now()->toDateString(),
                        auth()->id(),
                        'approved'
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'S', // IFA Standard
                'message' => 'Redemption approved successfully',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'E', // IFA Standard: E (Error)
                'message' => 'Failed to approve redemption',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 500);
        }
    }

    /**
     * Reject redemption (Admin only)
     */
    public function rejectRedemption(Request $request, $id)
    {
        $redemption = DB::table('user_reward')->where('id', $id)->first();
        
        if (!$redemption) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Redemption not found',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 404);
        }

        // Refund points
        $user = User::find($redemption->user_id);
        if ($user) {
            $user->loyaltyPoints()->create([
                'points' => $redemption->points_used,
                'action_type' => 'redemption_refund',
                'description' => "Refunded for rejected redemption #{$id}",
            ]);
        }

        DB::table('user_reward')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Redemption rejected and points refunded',
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get all certificates (Admin only)
     */
    public function getAllCertificates()
    {
        $certificates = Certificate::with('user')->latest()->get();
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $certificates,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Issue a certificate to a user (Admin only)
     */
    public function issueCertificate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reward_id' => 'nullable|exists:rewards,id',
            'issued_date' => 'nullable|date',
        ]);

        $user = User::findOrFail($request->user_id);

        $certificate = LoyaltyFactory::makeCertificate(
            $request->user_id,
            $request->title,
            $request->reward_id,
            $request->description,
            $request->issued_date ?? null,
            auth()->id(),
            'approved' // Certificate is approved when issued
        );

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Certificate issued successfully',
            'data' => $certificate->load('user'),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    /**
     * Get participation report (Admin only)
     */
    public function getParticipationReport(Request $request)
    {
        // 默认按当前月份统计（可通过 start_date / end_date 覆盖）
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $report = [
            'total_users' => User::where('role', 'student')->count(),
            'active_users' => LoyaltyPoint::whereBetween('created_at', [$startDate, $endDate])
                ->distinct('user_id')
                ->count('user_id'),
            'total_points_awarded' => LoyaltyPoint::where('points', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('points'),
            'top_earners' => User::where('role', 'student')
                ->withSum('loyaltyPoints as total_points', 'points')
                ->orderByDesc('total_points')
                ->limit(10)
                ->get(['id', 'name', 'email', 'total_points']),
            'points_by_action' => LoyaltyPoint::whereBetween('created_at', [$startDate, $endDate])
                ->where('points', '>', 0)
                ->select('action_type', DB::raw('sum(points) as total_points'), DB::raw('count(*) as count'))
                ->groupBy('action_type')
                ->get(),
        ];

        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $report,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get points distribution report (Admin only)
     */
    public function getPointsDistribution(Request $request)
    {
        // 按日期范围统计每个学生在该期间内获取的积分
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $distribution = User::where('role', 'student')
            ->withSum(['loyaltyPoints as total_points' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }], 'points')
            ->get()
            ->map(function($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'total_points' => $user->total_points ?? 0,
                ];
            })
            ->sortByDesc('total_points')
            ->values();

        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $distribution,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get rewards statistics (Admin only)
     */
    public function getRewardsStats(Request $request)
    {
        // 日期范围主要影响兑换相关统计
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $stats = [
            'total_rewards' => Reward::count(),
            'active_rewards' => Reward::where('is_active', true)->count(),
            'total_redemptions' => DB::table('user_reward')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'pending_redemptions' => DB::table('user_reward')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'pending')
                ->count(),
            'approved_redemptions' => DB::table('user_reward')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'approved')
                ->count(),
            'popular_rewards' => DB::table('user_reward')
                ->join('rewards', 'user_reward.reward_id', '=', 'rewards.id')
                ->whereBetween('user_reward.created_at', [$startDate, $endDate])
                ->select('rewards.name', DB::raw('count(*) as redemption_count'))
                ->groupBy('rewards.id', 'rewards.name')
                ->orderByDesc('redemption_count')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $stats,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * 导出 Loyalty Program 报表为 PDF（Admin）
     */
    public function exportReportsPdf(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        // 复用与 API 报表一致的统计逻辑
        $participation = [
            'total_users' => User::where('role', 'student')->count(),
            'active_users' => LoyaltyPoint::whereBetween('created_at', [$startDate, $endDate])
                ->distinct('user_id')
                ->count('user_id'),
            'total_points_awarded' => LoyaltyPoint::where('points', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('points'),
            'top_earners' => User::where('role', 'student')
                ->withSum(['loyaltyPoints as total_points' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }], 'points')
                ->orderByDesc('total_points')
                ->limit(10)
                ->get(['id', 'name', 'email']),
        ];

        $distribution = User::where('role', 'student')
            ->withSum(['loyaltyPoints as total_points' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }], 'points')
            ->get()
            ->sortByDesc('total_points')
            ->values();

        $rewardsStats = [
            'total_rewards' => Reward::count(),
            'active_rewards' => Reward::where('is_active', true)->count(),
            'total_redemptions' => DB::table('user_reward')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'pending_redemptions' => DB::table('user_reward')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'pending')
                ->count(),
            'approved_redemptions' => DB::table('user_reward')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'approved')
                ->count(),
            'popular_rewards' => DB::table('user_reward')
                ->join('rewards', 'user_reward.reward_id', '=', 'rewards.id')
                ->whereBetween('user_reward.created_at', [$startDate, $endDate])
                ->select('rewards.name', DB::raw('count(*) as redemption_count'))
                ->groupBy('rewards.id', 'rewards.name')
                ->orderByDesc('redemption_count')
                ->limit(10)
                ->get(),
        ];

        $pdf = Pdf::loadView('admin.loyalty.reports_pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'participation' => $participation,
            'distribution' => $distribution,
            'rewardsStats' => $rewardsStats,
        ])->setPaper('A4', 'portrait');

        $fileName = 'loyalty-reports-' . $startDate . '-to-' . $endDate . '.pdf';

        return $pdf->download($fileName);
    }
}
