<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $feedbacks = Feedback::with(['user', 'facility'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json(['data' => $feedbacks]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'facility_id' => 'nullable|exists:facilities,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'type' => 'required|in:complaint,suggestion,compliment,general',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'image' => 'nullable|string', // base64 image string
        ]);

        // Validate base64 image if provided
        if ($request->has('image') && $request->image) {
            // Check if it's a valid base64 image string
            if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $request->image)) {
                return response()->json([
                    'message' => 'Invalid image format. Please upload a valid image (JPG, PNG, or GIF).'
                ], 422);
            }
            
            // Check base64 string length (limit to ~1.5MB base64, which is ~1MB actual image)
            // Base64 encoding increases size by ~33%, so 1.5MB base64 ≈ 1MB image
            if (strlen($request->image) > 1500000) {
                return response()->json([
                    'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.'
                ], 422);
            }
        }

        $feedback = Feedback::create($validated + ['user_id' => auth()->id()]);
        return response()->json(['data' => $feedback], 201);
    }

    public function show(Request $request, string $id)
    {
        $feedback = Feedback::with(['user', 'facility', 'reviewer'])->findOrFail($id);
        
        // Allow users to view their own feedbacks, or admin to view any
        if (!$request->user()->isAdmin() && $feedback->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // If admin/staff views a pending feedback, automatically change status to under_review
        // This makes under_review mean "admin has seen it and is reviewing/processing it"
        if (($request->user()->isAdmin() || $request->user()->isStaff()) 
            && $feedback->status === 'pending') {
            $feedback->update([
                'status' => 'under_review',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            // Reload to get updated data with reviewer relationship
            $feedback->refresh();
            $feedback->load('reviewer');
        }
        
        return response()->json(['data' => $feedback]);
    }

    public function myFeedbacks(Request $request)
    {
        $feedbacks = Feedback::with(['facility'])
            ->where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json(['data' => $feedbacks]);
    }

    public function update(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update($request->all());
        return response()->json(['data' => $feedback]);
    }

    public function destroy(string $id)
    {
        Feedback::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function respond(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'admin_response' => $request->response,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'status' => 'resolved',
        ]);

        // 忠诚度积分：反馈被解决时触发 feedback_resolved 规则
        try {
            $rule = LoyaltyRule::where('action_type', 'feedback_resolved')
                ->where('is_active', true)
                ->first();

            if ($rule) {
                // 防止重复奖励：同一条反馈、同一 action_type 只奖励一次
                $existingPoint = LoyaltyPoint::where('user_id', $feedback->user_id)
                    ->where('action_type', 'feedback_resolved')
                    ->where('related_id', $feedback->id)
                    ->where('related_type', Feedback::class)
                    ->first();

                if (!$existingPoint) {
                    LoyaltyPoint::create([
                        'user_id' => $feedback->user_id,
                        'points' => $rule->points,
                        'action_type' => 'feedback_resolved',
                        'related_id' => $feedback->id,
                        'related_type' => Feedback::class,
                        'description' => $rule->description ?? "Feedback resolved: {$feedback->subject}",
                    ]);
                }
            }
        } catch (\Exception $e) {
            // 出现错误时不影响反馈本身的更新
        }

        return response()->json(['data' => $feedback]);
    }

    public function block(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'is_blocked' => true,
            'block_reason' => $request->reason,
            'status' => 'blocked',
        ]);
        return response()->json(['data' => $feedback]);
    }

    public function reject(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        return response()->json(['data' => $feedback]);
    }
}
