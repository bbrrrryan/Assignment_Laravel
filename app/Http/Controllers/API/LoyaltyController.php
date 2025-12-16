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

class LoyaltyController extends Controller
{
    
    public function getPoints()
    {
        $points = auth()->user()->loyaltyPoints()->sum('points');
        return response()->json(['total_points' => $points]);
    }

    public function pointsHistory(Request $request)
    {
        $history = auth()->user()->loyaltyPoints()
            ->latest()
            ->paginate(15);
        return response()->json(['data' => $history]);
    }

    public function getRewards()
    {
        return response()->json(['data' => Reward::where('is_active', true)->get()]);
    }

    public function redeemReward(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        $user = auth()->user();
        $reward = Reward::findOrFail($request->reward_id);

        if (!$reward->is_active) {
            return response()->json(['message' => 'This reward is not available'], 400);
        }

        $totalPoints = $user->loyaltyPoints()->sum('points');
        if ($totalPoints < $reward->points_required) {
            return response()->json([
                'message' => 'Insufficient points. Required: ' . $reward->points_required . ', Available: ' . $totalPoints,
            ], 400);
        }

        if ($reward->stock_quantity !== null && $reward->stock_quantity <= 0) {
            return response()->json(['message' => 'This reward is out of stock'], 400);
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
                'message' => 'Reward redeemed successfully. Awaiting approval.',
                'data' => [
                    'reward' => $reward,
                    'points_used' => $reward->points_required,
                    'remaining_points' => $totalPoints - $reward->points_required,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to redeem reward',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCertificates()
    {
        return response()->json(['data' => auth()->user()->certificates]);
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
            'message' => 'Points awarded successfully',
            'data' => $point->load('user'),
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
            'message' => 'Points deducted successfully',
            'data' => $point->load('user'),
        ], 201);
    }

    /**
     * Get all users' points (Admin only)
     * Only shows students, excludes staff and admin users
     */
    public function getAllUsersPoints(Request $request)
    {
        // Only show students, exclude staff and admin
        $query = User::with('loyaltyPoints')
            ->where('role', 'student');
        
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

        return response()->json(['data' => $users]);
    }

    /**
     * Get specific user's points (Admin only)
     */
    public function getUserPoints($userId)
    {
        $user = User::with('loyaltyPoints')->findOrFail($userId);
        
        return response()->json([
            'data' => [
                'user' => $user,
                'total_points' => $user->loyaltyPoints()->sum('points'),
                'points_history' => $user->loyaltyPoints()->latest()->paginate(15),
            ],
        ]);
    }

    /**
     * Get all loyalty rules (Admin only)
     */
    public function getRules()
    {
        $rules = LoyaltyRule::latest()->get();
        return response()->json(['data' => $rules]);
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
        return response()->json(['message' => 'Rule created successfully', 'data' => $rule], 201);
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
        return response()->json(['message' => 'Rule updated successfully', 'data' => $rule]);
    }

    /**
     * Delete loyalty rule (Admin only)
     */
    public function deleteRule($id)
    {
        $rule = LoyaltyRule::findOrFail($id);
        $rule->delete();
        return response()->json(['message' => 'Rule deleted successfully']);
    }

    /**
     * Get all rewards (Admin only - includes inactive)
     */
    public function getAllRewards()
    {
        $rewards = Reward::latest()->get();
        return response()->json(['data' => $rewards]);
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
            'reward_type' => 'required|in:certificate,badge,privilege,physical',
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
                        'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.'
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
        return response()->json(['message' => 'Reward created successfully', 'data' => $reward], 201);
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
            'reward_type' => 'required|in:certificate,badge,privilege,physical',
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
                        'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.'
                    ], 422);
                }
            }
        }

        $reward->update($validated);
        return response()->json(['message' => 'Reward updated successfully', 'data' => $reward]);
    }

    /**
     * Delete reward (Admin only)
     */
    public function deleteReward($id)
    {
        $reward = Reward::findOrFail($id);
        $reward->delete();
        return response()->json(['message' => 'Reward deleted successfully']);
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
        return response()->json(['data' => $redemptions]);
    }

    /**
     * Approve redemption (Admin only)
     */
    public function approveRedemption(Request $request, $id)
    {
        $redemption = DB::table('user_reward')->where('id', $id)->first();
        
        if (!$redemption) {
            return response()->json(['message' => 'Redemption not found'], 404);
        }

        DB::table('user_reward')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Redemption approved successfully']);
    }

    /**
     * Reject redemption (Admin only)
     */
    public function rejectRedemption(Request $request, $id)
    {
        $redemption = DB::table('user_reward')->where('id', $id)->first();
        
        if (!$redemption) {
            return response()->json(['message' => 'Redemption not found'], 404);
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

        return response()->json(['message' => 'Redemption rejected and points refunded']);
    }

    /**
     * Get all certificates (Admin only)
     */
    public function getAllCertificates()
    {
        $certificates = Certificate::with('user')->latest()->get();
        return response()->json(['data' => $certificates]);
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
            'message' => 'Certificate issued successfully',
            'data' => $certificate->load('user'),
        ], 201);
    }

    /**
     * Get participation report (Admin only)
     */
    public function getParticipationReport(Request $request)
    {
        $startDate = $request->start_date ?? now()->subDays(30)->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

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

        return response()->json(['data' => $report]);
    }

    /**
     * Get points distribution report (Admin only)
     */
    public function getPointsDistribution()
    {
        $distribution = User::where('role', 'student')
            ->withSum('loyaltyPoints as total_points', 'points')
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

        return response()->json(['data' => $distribution]);
    }

    /**
     * Get rewards statistics (Admin only)
     */
    public function getRewardsStats()
    {
        $stats = [
            'total_rewards' => Reward::count(),
            'active_rewards' => Reward::where('is_active', true)->count(),
            'total_redemptions' => DB::table('user_reward')->count(),
            'pending_redemptions' => DB::table('user_reward')->where('status', 'pending')->count(),
            'approved_redemptions' => DB::table('user_reward')->where('status', 'approved')->count(),
            'popular_rewards' => DB::table('user_reward')
                ->join('rewards', 'user_reward.reward_id', '=', 'rewards.id')
                ->select('rewards.name', DB::raw('count(*) as redemption_count'))
                ->groupBy('rewards.id', 'rewards.name')
                ->orderByDesc('redemption_count')
                ->limit(10)
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }
}
