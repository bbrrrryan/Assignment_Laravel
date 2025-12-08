<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;
use Illuminate\Http\Request;

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

        $point = LoyaltyPoint::create([
            'user_id' => $request->user_id,
            'points' => $request->points,
            'action_type' => $request->action_type,
            'description' => $request->description ?? "Points awarded for: {$request->action_type}",
            'awarded_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Points awarded successfully',
            'data' => $point->load('user'),
        ], 201);
    }

    public function getRewards()
    {
        return response()->json(['data' => Reward::where('is_active', true)->get()]);
    }

    /**
     * Redeem a reward using loyalty points
     */
    public function redeemReward(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        $user = auth()->user();
        $reward = Reward::findOrFail($request->reward_id);

        // Check if reward is active
        if (!$reward->is_active) {
            return response()->json([
                'message' => 'This reward is not available',
            ], 400);
        }

        // Check if user has enough points
        $totalPoints = $user->loyaltyPoints()->sum('points');
        if ($totalPoints < $reward->points_required) {
            return response()->json([
                'message' => 'Insufficient points. Required: ' . $reward->points_required . ', Available: ' . $totalPoints,
            ], 400);
        }

        // Check stock if applicable
        if ($reward->stock_quantity !== null && $reward->stock_quantity <= 0) {
            return response()->json([
                'message' => 'This reward is out of stock',
            ], 400);
        }

        // Create redemption record
        DB::beginTransaction();
        try {
            // Deduct points
            $user->loyaltyPoints()->create([
                'points' => -$reward->points_required,
                'action_type' => 'reward_redemption',
                'description' => "Redeemed reward: {$reward->name}",
            ]);

            // Attach reward to user
            $user->rewards()->attach($reward->id, [
                'points_used' => $reward->points_required,
                'status' => 'pending',
                'redeemed_at' => now(),
            ]);

            // Decrease stock if applicable
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

    /**
     * Issue a certificate to a user (Admin only)
     */
    public function issueCertificate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'certificate_type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'nullable|integer|min:0',
            'issued_date' => 'nullable|date',
        ]);

        $user = User::findOrFail($request->user_id);

        // Check if user has enough points if required
        if ($request->points_required) {
            $totalPoints = $user->loyaltyPoints()->sum('points');
            if ($totalPoints < $request->points_required) {
                return response()->json([
                    'message' => 'User does not have enough points for this certificate',
                ], 400);
            }
        }

        $certificate = Certificate::create([
            'user_id' => $request->user_id,
            'certificate_type' => $request->certificate_type,
            'title' => $request->title,
            'description' => $request->description,
            'points_required' => $request->points_required,
            'issued_by' => auth()->id(),
            'issued_date' => $request->issued_date ?? now(),
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Certificate issued successfully',
            'data' => $certificate->load('user'),
        ], 201);
    }
}
