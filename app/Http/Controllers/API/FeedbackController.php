<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use App\Models\Notification;
use App\Models\User;
use App\Factories\FeedbackFactory;
use App\Factories\LoyaltyFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $feedbacks = Feedback::with(['user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where(function($query) use ($request) {
                $query->where('subject', 'like', "%{$request->search}%")
                      ->orWhere('message', 'like', "%{$request->search}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(10)->withQueryString();
        
        // Note: facility_id removed, now using facility_type directly
        // No need to fetch facility info via HTTP service
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedbacks,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get pending feedbacks for admin dropdown
     */
    public function getPendingFeedbacks(Request $request)
    {
        $limit = $request->get('limit', 10);

        $feedbacks = Feedback::with(['user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'subject' => $feedback->subject,
                    'type' => $feedback->type,
                    'user_name' => $feedback->user->name ?? 'Unknown',
                    'facility_type' => $feedback->facility_type ?? 'N/A',
                    'created_at' => $feedback->created_at,
                ];
            });

        $count = Feedback::where('status', 'pending')->count();

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Pending feedbacks retrieved successfully',
            'data' => [
                'feedbacks' => $feedbacks,
                'count' => $count,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
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
        
        // Validate that booking belongs to the authenticated user (if provided)
        if (isset($validated['booking_id'])) {
            $booking = \App\Models\Booking::find($validated['booking_id']);
            if (!$booking) {
                return response()->json([
                    'status' => 'F',
                    'message' => 'Booking not found',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 404);
            }
            
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'status' => 'F',
                    'message' => 'You can only associate feedback with your own bookings',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 403);
            }
            
            // Check if this booking already has feedback
            $existingFeedback = Feedback::where('booking_id', $validated['booking_id'])
                ->where('user_id', auth()->id())
                ->first();
            
            if ($existingFeedback) {
                return response()->json([
                    'status' => 'F',
                    'message' => 'This booking has already been rated. You cannot submit multiple feedbacks for the same booking.',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 422);
            }
        }

        // Validate base64 image if provided
        if ($request->has('image') && $request->image) {
            // Check if it's a valid base64 image string
            if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $request->image)) {
                return response()->json([
                    'status' => 'F', // IFA Standard: F (Fail)
                    'message' => 'Invalid image format. Please upload a valid image (JPG, PNG, or GIF).',
                    'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                ], 422);
            }
            
            // Check base64 string length (limit to ~1.5MB base64, which is ~1MB actual image)
            // Base64 encoding increases size by ~33%, so 1.5MB base64 ≈ 1MB image
            if (strlen($request->image) > 1500000) {
                return response()->json([
                    'status' => 'F', // IFA Standard: F (Fail)
                    'message' => 'Image is too large. Maximum size is 1MB. Please compress your image before uploading.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                ], 422);
            }
        }

        $feedback = FeedbackFactory::makeFeedback(
            auth()->id(),
            $validated['type'],
            $validated['subject'],
            $validated['message'],
            $validated['rating'],
            $validated['facility_id'] ?? null,
            $validated['booking_id'] ?? null,
            $validated['image'] ?? null,
            'pending' // Default status
        );
        
        // Don't send "Feedback Submitted" confirmation to students
        // Only notify admins about new feedback
        $this->notifyAdminsAboutFeedback($feedback);
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedback,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $feedback = Feedback::with(['user', 'reviewer'])->findOrFail($id);
        
        // Allow users to view their own feedbacks, or admin to view any
        if (!$request->user()->isAdmin() && $feedback->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Unauthorized',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 403);
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
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedback,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function myFeedbacks(Request $request)
    {
        $feedbacks = Feedback::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where(function($query) use ($request) {
                $query->where('subject', 'like', "%{$request->search}%")
                      ->orWhere('message', 'like', "%{$request->search}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(10)->withQueryString();
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedbacks,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Get feedbacks for a specific facility type
     */
    public function getFacilityFeedbacks(Request $request, string $facilityType)
    {
        $feedbacks = Feedback::with(['user'])
            ->where('facility_type', $facilityType)
            ->where('is_blocked', false) // Only show non-blocked feedbacks
            ->where('status', '!=', 'rejected') // Exclude rejected feedbacks
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedbacks,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function destroy(string $id)
    {
        Feedback::findOrFail($id)->delete();
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Deleted',
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function respond(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $originalStatus = $feedback->status;
        
        $feedback->update([
            'admin_response' => $request->response,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'status' => 'resolved',
        ]);

        // Reload to get updated relationships
        $feedback->refresh();
        $feedback->load(['user', 'reviewer']);

        // Loyalty points: Trigger feedback_resolved rule when feedback is resolved
        try {
            $rule = LoyaltyRule::where('action_type', 'feedback_resolved')
                ->where('is_active', true)
                ->first();

            if ($rule) {
                // Prevent duplicate rewards: Only award once per feedback for the same action_type
                $existingPoint = LoyaltyPoint::where('user_id', $feedback->user_id)
                    ->where('action_type', 'feedback_resolved')
                    ->where('related_id', $feedback->id)
                    ->where('related_type', Feedback::class)
                    ->first();

                if (!$existingPoint) {
                    LoyaltyFactory::makeLoyaltyPoint(
                        $feedback->user_id,
                        $rule->points,
                        'feedback_resolved',
                        $rule->description ?? "Feedback resolved: {$feedback->subject}",
                        $feedback->id,
                        Feedback::class
                    );
                }
            }
        } catch (\Exception $e) {
            // Errors should not affect the feedback update itself
            Log::error("Error awarding loyalty points for feedback resolution: " . $e->getMessage());
        }

        // Send notification to user when feedback is resolved
        if ($originalStatus !== 'resolved') {
            $this->notifyUserAboutFeedbackResolution($feedback);
        }

        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedback,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function block(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'is_blocked' => true,
            'block_reason' => $request->reason,
            'status' => 'blocked',
        ]);
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedback,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $feedback,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Send confirmation notification to student who submitted the feedback
     */
    protected function notifyStudentAboutFeedbackSubmission(Feedback $feedback): void
    {
        try {
            // Load related data
            $feedback->load(['user']);
            
            // Build notification title and message in English
            $facilityType = $feedback->facility_type ? ucfirst($feedback->facility_type) : 'N/A';
            $typeLabels = [
                'complaint' => 'Complaint',
                'suggestion' => 'Suggestion',
                'compliment' => 'Compliment',
                'general' => 'General Feedback',
            ];
            $typeLabel = $typeLabels[$feedback->type] ?? ucfirst($feedback->type);
            
            $title = "Feedback Submitted Successfully";
            $message = "Your {$typeLabel} has been submitted successfully:\n\n";
            $message .= "Subject: {$feedback->subject}\n";
            if ($feedback->facility_type) {
                $message .= "Facility Type: {$facilityType}\n";
            }
            $message .= "Rating: {$feedback->rating}/5\n";
            $message .= "Feedback ID: #{$feedback->id}\n\n";
            $message .= "Our team will review your feedback and respond soon.";
            
            // Create notification for the student
            $notification = Notification::create([
                'title' => $title,
                'message' => $message,
                'type' => 'success',
                'priority' => 'medium',
                'created_by' => $feedback->user_id,
                'target_audience' => 'specific',
                'target_user_ids' => [$feedback->user_id],
                'is_active' => true,
                'scheduled_at' => now(),
            ]);
            
            // Sync notification to the student
            $notification->users()->sync([
                $feedback->user_id => [
                    'is_read' => false,
                    'is_acknowledged' => false,
                ]
            ]);
            
            Log::info("Feedback submission notification sent to student", [
                'notification_id' => $notification->id,
                'feedback_id' => $feedback->id,
                'user_id' => $feedback->user_id
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send feedback submission notification to student: " . $e->getMessage(), [
                'feedback_id' => $feedback->id ?? null
            ]);
        }
    }

    /**
     * Send notification to user when their feedback is resolved
     */
    protected function notifyUserAboutFeedbackResolution(Feedback $feedback): void
    {
        try {
            // Load related data
            $feedback->load(['user', 'reviewer']);
            
            if (!$feedback->user) {
                Log::warning("Cannot send resolution notification: feedback has no user", [
                    'feedback_id' => $feedback->id
                ]);
                return;
            }
            
            // Build notification title and message in English
            $reviewerName = $feedback->reviewer->name ?? 'Administrator';
            $adminResponse = $feedback->admin_response ?? 'Your feedback has been reviewed and resolved.';
            
            $title = "Feedback Resolved - {$feedback->subject}";
            $message = "Your feedback has been resolved by {$reviewerName}.\n\n";
            $message .= "Subject: {$feedback->subject}\n";
            $message .= "Feedback ID: #{$feedback->id}\n\n";
            $message .= "Admin Response:\n{$adminResponse}\n\n";
            $message .= "You can view the full details in your feedback history.";
            
            // Create notification for the user
            $notification = Notification::create([
                'title' => $title,
                'message' => $message,
                'type' => 'success',
                'priority' => 'medium',
                'created_by' => $feedback->reviewed_by ?? auth()->id(),
                'target_audience' => 'specific',
                'target_user_ids' => [$feedback->user_id],
                'is_active' => true,
                'scheduled_at' => now(),
            ]);
            
            // Sync notification to the user
            $notification->users()->sync([
                $feedback->user_id => [
                    'is_read' => false,
                    'is_acknowledged' => false,
                ]
            ]);
            
            Log::info("Feedback resolution notification sent to user", [
                'notification_id' => $notification->id,
                'feedback_id' => $feedback->id,
                'user_id' => $feedback->user_id,
                'reviewer_id' => $feedback->reviewed_by
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send feedback resolution notification to user: " . $e->getMessage(), [
                'feedback_id' => $feedback->id ?? null,
                'exception' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send notification to all admin users about new feedback submission
     */
    protected function notifyAdminsAboutFeedback(Feedback $feedback): void
    {
        try {
            // Load related data
            $feedback->load(['user']);
            
            // Build notification title and message in English
            $userName = $feedback->user->name ?? 'A user';
            $facilityType = $feedback->facility_type ? ucfirst($feedback->facility_type) : 'N/A';
            $typeLabels = [
                'complaint' => 'Complaint',
                'suggestion' => 'Suggestion',
                'compliment' => 'Compliment',
                'general' => 'General Feedback',
            ];
            $typeLabel = $typeLabels[$feedback->type] ?? ucfirst($feedback->type);
            
            $title = "New Feedback Submitted - {$typeLabel}";
            $message = "{$userName} has submitted a new feedback:\n\n";
            $message .= "Subject: {$feedback->subject}\n";
            $message .= "Type: {$typeLabel}\n";
            if ($feedback->facility_type) {
                $message .= "Facility Type: {$facilityType}\n";
            }
            $message .= "Rating: {$feedback->rating}/5\n";
            $message .= "Feedback ID: #{$feedback->id}";
            
            // Get all active admin user IDs
            $adminUserIds = User::where('status', 'active')
                ->where('role', 'admin')
                ->pluck('id')
                ->toArray();
            
            if (empty($adminUserIds)) {
                Log::warning('No active admin users found to notify about feedback #' . $feedback->id);
                return;
            }
            
            // Create notification
            $notification = Notification::create([
                'title' => $title,
                'message' => $message,
                'type' => 'info',
                'priority' => 'medium',
                'created_by' => $feedback->user_id,
                'target_audience' => 'specific',
                'target_user_ids' => $adminUserIds,
                'is_active' => true,
                'scheduled_at' => now(),
            ]);
            
            // Sync notification to all admin users
            $syncData = [];
            foreach ($adminUserIds as $adminId) {
                $syncData[$adminId] = [
                    'is_read' => false,
                    'is_acknowledged' => false,
                ];
            }
            $notification->users()->sync($syncData);
            
            Log::info("Feedback notification sent to " . count($adminUserIds) . " admin(s)", [
                'notification_id' => $notification->id,
                'feedback_id' => $feedback->id
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send feedback notification to admins: " . $e->getMessage(), [
                'feedback_id' => $feedback->id ?? null
            ]);
        }
    }

    /**
     * Get booking details for a feedback (if feedback is related to booking)
     * This endpoint uses Booking Module's web service to retrieve booking information
     * 
     * IFA Standard Compliance:
     * - Request must include timestamp or requestID (mandatory)
     * - Response includes status and timestamp (mandatory)
     */
    public function getBookingDetailsForFeedback(Request $request, string $id)
    {
        // IFA Standard: Validate mandatory fields (timestamp or requestID)
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

        $feedback = Feedback::with(['user', 'facility'])->findOrFail($id);
        
        // Check if user has permission to view this feedback
        $user = $request->user();
        if (!$user->isAdmin() && $feedback->user_id !== $user->id) {
            return response()->json([
                'status' => 'F',
                'message' => 'Unauthorized. You can only view booking details for your own feedbacks.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 403);
        }
        
        // Check if feedback is related to a booking
        if (!$feedback->booking_id) {
            return response()->json([
                'status' => 'F',
                'message' => 'This feedback is not related to a booking',
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'subject' => $feedback->subject,
                        'type' => $feedback->type,
                    ],
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }
        
        // Use Web Service to get booking information from Booking Module
        $baseUrl = config('app.url', 'http://localhost:8000');
        $apiUrl = rtrim($baseUrl, '/') . '/api/bookings/service/get-info';
        
        try {
            $response = Http::timeout(10)->post($apiUrl, [
                'booking_id' => $feedback->booking_id,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'S') {
                    // IFA Standard Response Format
                    return response()->json([
                        'status' => 'S',
                        'message' => 'Booking details retrieved successfully',
                        'data' => [
                            'feedback' => [
                                'id' => $feedback->id,
                                'subject' => $feedback->subject,
                                'type' => $feedback->type,
                                'rating' => $feedback->rating,
                                'status' => $feedback->status,
                                'created_at' => $feedback->created_at ? $feedback->created_at->format('Y-m-d H:i:s') : null,
                            ],
                            'booking' => $data['data']['booking'],
                        ],
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ]);
                } else {
                    // Booking service returned error
                    Log::warning("Booking service returned error for booking #{$feedback->booking_id}", [
                        'feedback_id' => $feedback->id,
                        'booking_response' => $data,
                    ]);
                }
            } else {
                Log::warning("Failed to get booking info from Booking Module", [
                    'feedback_id' => $feedback->id,
                    'booking_id' => $feedback->booking_id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
            
            // Fallback to direct query if web service fails
            Log::info("Falling back to direct query for booking #{$feedback->booking_id}");
            $booking = \App\Models\Booking::with(['user', 'facility', 'attendees', 'slots'])
                ->findOrFail($feedback->booking_id);
            
            return response()->json([
                'status' => 'S',
                'message' => 'Booking details retrieved successfully (via direct query)',
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'subject' => $feedback->subject,
                        'type' => $feedback->type,
                        'rating' => $feedback->rating,
                        'status' => $feedback->status,
                        'created_at' => $feedback->created_at ? $feedback->created_at->format('Y-m-d H:i:s') : null,
                    ],
                    'booking' => [
                        'id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'user_name' => $booking->user->name ?? null,
                        'user_email' => $booking->user->email ?? null,
                        'facility_id' => $booking->facility_id,
                        'facility_name' => $booking->facility->name ?? null,
                        'facility_code' => $booking->facility->code ?? null,
                        'booking_date' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                        'start_time' => $booking->start_time ? $booking->start_time->format('Y-m-d H:i:s') : null,
                        'end_time' => $booking->end_time ? $booking->end_time->format('Y-m-d H:i:s') : null,
                        'duration_hours' => $booking->duration_hours,
                        'purpose' => $booking->purpose,
                        'status' => $booking->status,
                        'expected_attendees' => $booking->expected_attendees,
                        'created_at' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
                    ],
                    'attendees_count' => $booking->attendees->count(),
                    'slots_count' => $booking->slots->count(),
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get booking details for feedback #{$id}: " . $e->getMessage(), [
                'feedback_id' => $feedback->id,
                'booking_id' => $feedback->booking_id,
                'exception' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'E',
                'message' => 'Failed to retrieve booking details',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
