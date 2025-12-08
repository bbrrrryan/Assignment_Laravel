<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $feedbacks = Feedback::with(['user', 'facility'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(15);
        return response()->json(['data' => $feedbacks]);
    }

    public function store(Request $request)
    {
        $feedback = Feedback::create($request->validate([
            'facility_id' => 'nullable|exists:facilities,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'type' => 'required|in:complaint,suggestion,compliment,general',
            'subject' => 'required',
            'message' => 'required',
            'rating' => 'nullable|integer|min:1|max:5',
        ]) + ['user_id' => auth()->id()]);
        return response()->json(['data' => $feedback], 201);
    }

    public function show(Request $request, string $id)
    {
        $feedback = Feedback::with(['user', 'facility'])->findOrFail($id);
        
        // Allow users to view their own feedbacks, or admin to view any
        if (!$request->user()->isAdmin() && $feedback->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
}
