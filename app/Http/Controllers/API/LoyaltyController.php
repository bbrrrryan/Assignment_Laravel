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
            'status' => 'S',
            'data' => ['total_points' => $points],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function pointsHistory(Request $request)
    {
        $history = auth()->user()->loyaltyPoints()
            ->latest()
            ->paginate(15);
        return response()->json([
            'status' => 'S',
            'data' => $history,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getRewards()
    {
        return response()->json([
            'status' => 'S',
            'data' => Reward::where('is_active', true)->get(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function showReward($id)
    {
        $reward = Reward::findOrFail($id);
        
        if (!$reward->is_active && !auth()->user()->isAdmin()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Reward not found',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }
        
        return response()->json([
            'status' => 'S',
            'data' => $reward,
            'timestamp' => now()->format('Y-m-d H:i:s'),
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
                'status' => 'F',
                'message' => 'This reward is not available',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 400);
        }

        if ($reward->reward_type === 'certificate') {
            $alreadyRedeemed = DB::table('user_reward')
                ->where('user_id', $user->id)
                ->where('reward_id', $reward->id)
                ->whereIn('status', ['pending', 'approved', 'redeemed'])
                ->exists();

            if ($alreadyRedeemed) {
                return response()->json([
                    'status' => 'F',
                    'message' => 'You have already redeemed this certificate reward.',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 400);
            }
        }

        $totalPoints = $user->loyaltyPoints()->sum('points');
        if ($totalPoints < $reward->points_required) {
            return response()->json([
                'status' => 'F',
                'message' => 'Insufficient points. Required: ' . $reward->points_required . ', Available: ' . $totalPoints,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 400);
        }

        if ($reward->stock_quantity !== null && $reward->stock_quantity <= 0) {
            return response()->json([
                'status' => 'F',
                'message' => 'This reward is out of stock',
                'timestamp' => now()->format('Y-m-d H:i:s'),
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
                'status' => 'S',
                'message' => 'Reward redeemed successfully. Awaiting approval.',
                'data' => [
                    'reward' => $reward,
                    'points_used' => $reward->points_required,
                    'remaining_points' => $totalPoints - $reward->points_required,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'E',
                'message' => 'Failed to redeem reward',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getCertificates()
    {
        return response()->json([
            'status' => 'S',
            'data' => auth()->user()->certificates,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function myRewards(Request $request)
    {
        $user = auth()->user();

        $query = DB::table('user_reward')
            ->leftJoin('rewards', 'user_reward.reward_id', '=', 'rewards.id')
            ->where('user_reward.user_id', $user->id)
            ->select(
                'user_reward.id as redemption_id',
                'user_reward.reward_id',
                'user_reward.points_used',
                'user_reward.status',
                'user_reward.approved_by',
                'user_reward.redeemed_at',
                'user_reward.created_at',
                'rewards.name',
                'rewards.description',
                'rewards.points_required',
                'rewards.reward_type',
                'rewards.image_url'
            )
            ->orderBy('user_reward.created_at', 'desc');

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('user_reward.status', $request->status);
        }

        $rewards = $query->get()->map(function ($item) {
            return [
                'id' => $item->reward_id,
                'redemption_id' => $item->redemption_id,
                'name' => $item->name ?? 'Deleted Reward',
                'description' => $item->description ?? '',
                'points_required' => $item->points_required ?? $item->points_used,
                'reward_type' => $item->reward_type ?? 'unknown',
                'image_url' => $item->image_url,
                'points_used' => $item->points_used,
                'status' => $item->status,
                'approved_by' => $item->approved_by,
                'redeemed_at' => $item->redeemed_at,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'status' => 'S',
            'data' => $rewards,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

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
            'status' => 'S',
            'message' => 'Points awarded successfully',
            'data' => $point->load('user'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

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
            'status' => 'S',
            'message' => 'Points deducted successfully',
            'data' => $point->load('user'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function getAllUsersPoints(Request $request)
    {
        try {
            $baseUrl = config('app.url', 'http://localhost:8000');
            $apiUrl = rtrim($baseUrl, '/') . '/api/users/service/get-ids';
            
            $userResponse = Http::timeout(10)->post($apiUrl, [
                'role' => 'student',
                'status' => 'active',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if (!$userResponse->successful()) {
                Log::error('Failed to get user IDs from User Management Module', [
                    'status' => $userResponse->status(),
                    'response' => $userResponse->body(),
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception(
                    "User Web Service unavailable. HTTP Status: {$userResponse->status()}. " .
                    "Response: {$userResponse->body()}"
                );
            }
            
            $userData = $userResponse->json();
            
            if (!isset($userData['status']) || $userData['status'] !== 'S' || !isset($userData['data']['user_ids'])) {
                Log::error('User Web Service returned invalid response', [
                    'response' => $userData,
                    'url' => $apiUrl,
                ]);
                
                throw new \Exception("User Web Service returned invalid response format");
            }
            
            $userIds = $userData['data']['user_ids'];
            $query = User::with('loyaltyPoints')
                ->whereIn('id', $userIds);
                
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User Web Service connection exception', [
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
            
            return response()->json([
                'status' => 'F',
                'message' => 'Unable to retrieve user information. The user service is currently unavailable. Please try again later.',
                'error_details' => "Unable to connect to User Web Service: {$e->getMessage()}",
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 503);
        } catch (\Exception $e) {
            Log::error('User Web Service exception in getAllUsersPoints', [
                'error' => $e->getMessage(),
                'url' => $apiUrl ?? 'unknown',
            ]);
            
            return response()->json([
                'status' => 'F',
                'message' => 'Unable to retrieve user information. The user service is currently unavailable. Please try again later.',
                'error_details' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 503);
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
            'status' => 'S',
            'data' => $users,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getUserPoints($userId)
    {
        $user = User::with('loyaltyPoints')->findOrFail($userId);
        
        return response()->json([
            'status' => 'S',
            'data' => [
                'user' => $user,
                'total_points' => $user->loyaltyPoints()->sum('points'),
                'points_history' => $user->loyaltyPoints()->latest()->paginate(15),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getRules()
    {
        $rules = LoyaltyRule::latest()->get();
        return response()->json([
            'status' => 'S',
            'data' => $rules,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

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
            'status' => 'S',
            'message' => 'Rule created successfully',
            'data' => $rule,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

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
            'status' => 'S',
            'message' => 'Rule updated successfully',
            'data' => $rule,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function deleteRule($id)
    {
        $rule = LoyaltyRule::findOrFail($id);
        $rule->delete();
        return response()->json([
            'status' => 'S',
            'message' => 'Rule deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getAllRewards()
    {
        $rewards = Reward::latest()->get();
        return response()->json([
            'status' => 'S',
            'data' => $rewards,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function createReward(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:certificate,physical',
            'image_url' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('image_url') && $request->image_url) {
            if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $request->image_url)) {
                if (strlen($request->image_url) > 1500000) {
                    return response()->json([
                        'status' => 'F',
                        'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.',
                        'timestamp' => now()->format('Y-m-d H:i:s'),
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
            'status' => 'S',
            'message' => 'Reward created successfully',
            'data' => $reward,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

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

        if ($request->has('image_url') && $request->image_url) {
            if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $request->image_url)) {
                if (strlen($request->image_url) > 1500000) {
                    return response()->json([
                        'status' => 'F',
                        'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.',
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ], 422);
                }
            }
        }

        $reward->update($validated);
        return response()->json([
            'status' => 'S',
            'message' => 'Reward updated successfully',
            'data' => $reward,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function deleteReward($id)
    {
        $reward = Reward::findOrFail($id);
        $reward->delete();
        return response()->json([
            'status' => 'S',
            'message' => 'Reward deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

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
            'status' => 'S',
            'data' => $redemptions,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function approveRedemption(Request $request, $id)
    {
        $redemption = DB::table('user_reward')->where('id', $id)->first();
        
        if (!$redemption) {
            return response()->json([
                'status' => 'F',
                'message' => 'Redemption not found',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('user_reward')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            $reward = Reward::find($redemption->reward_id);
            if ($reward && $reward->reward_type === 'certificate') {
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
                'status' => 'S',
                'message' => 'Redemption approved successfully',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'E',
                'message' => 'Failed to approve redemption',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function rejectRedemption(Request $request, $id)
    {
        $redemption = DB::table('user_reward')->where('id', $id)->first();
        
        if (!$redemption) {
            return response()->json([
                'status' => 'F',
                'message' => 'Redemption not found',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

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
            'status' => 'S',
            'message' => 'Redemption rejected and points refunded',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getAllCertificates()
    {
        $certificates = Certificate::with('user')->latest()->get();
        return response()->json([
            'status' => 'S',
            'data' => $certificates,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

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
            'approved'
        );

        return response()->json([
            'status' => 'S',
            'message' => 'Certificate issued successfully',
            'data' => $certificate->load('user'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function getParticipationReport(Request $request)
    {
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
            'status' => 'S',
            'data' => $report,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getPointsDistribution(Request $request)
    {
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
            'status' => 'S',
            'data' => $distribution,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getRewardsStats(Request $request)
    {
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
            'status' => 'S',
            'data' => $stats,
            'timestamp' => now()->format('Y-m-d H:i:s'),    
        ]);
    }

    public function exportReportsPdf(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

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

    public function getUserLoyaltyInfo(Request $request)
    {
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
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->input('user_id');
        $includeHistory = $request->input('include_history', false);
        
        try {
            $user = User::with(['loyaltyPoints', 'certificates', 'rewards'])->findOrFail($userId);
            
            $totalPoints = $user->loyaltyPoints()->sum('points');
            
            $pointsByAction = LoyaltyPoint::where('user_id', $userId)
                ->select('action_type', DB::raw('sum(points) as total_points'), DB::raw('count(*) as count'))
                ->groupBy('action_type')
                ->get();
            
            $recentRewards = DB::table('user_reward')
                ->join('rewards', 'user_reward.reward_id', '=', 'rewards.id')
                ->where('user_reward.user_id', $userId)
                ->whereIn('user_reward.status', ['approved', 'redeemed'])
                ->select(
                    'rewards.id',
                    'rewards.name',
                    'rewards.reward_type',
                    'user_reward.points_used',
                    'user_reward.status',
                    'user_reward.redeemed_at'
                )
                ->orderBy('user_reward.redeemed_at', 'desc')
                ->limit(10)
                ->get();
            
            $certificatesCount = $user->certificates()->count();
            
            $responseData = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'total_points' => $totalPoints,
                'points_by_action' => $pointsByAction,
                'recent_rewards' => $recentRewards,
                'certificates_count' => $certificatesCount,
            ];
            
            if ($includeHistory) {
                $history = $user->loyaltyPoints()
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->map(function ($point) {
                        return [
                            'id' => $point->id,
                            'points' => $point->points,
                            'action_type' => $point->action_type,
                            'description' => $point->description,
                            'created_at' => $point->created_at ? $point->created_at->format('Y-m-d H:i:s') : null,
                        ];
                    });
                $responseData['points_history'] = $history;
            }
            
            return response()->json([
                'status' => 'S',
                'message' => 'User loyalty information retrieved successfully',
                'data' => $responseData,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to get user loyalty info: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'E',
                'message' => 'Failed to retrieve user loyalty information',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
