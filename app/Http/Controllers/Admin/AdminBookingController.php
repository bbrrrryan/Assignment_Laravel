<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Facility;
use App\Services\BookingCapacityService;
use App\Services\BookingNotificationService;
use Illuminate\Http\Request;

class AdminBookingController extends AdminBaseController
{
    protected $capacityService;
    protected $notificationService;

    public function __construct(
        BookingCapacityService $capacityService,
        BookingNotificationService $notificationService
    ) {
        parent::__construct();
        $this->capacityService = $capacityService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display all bookings (Admin view)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $bookings = Booking::with(['user', 'facility', 'slots'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->facility_id, fn($q) => $q->where('facility_id', $request->facility_id))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $bookings,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }


    /**
     * Approve a booking (Admin only)
     */
    public function approve(string $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'pending') {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Only pending bookings can be approved',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 400);
        }

        // Check capacity before approving
        $facility = $booking->facility;
        $expectedAttendees = $booking->expected_attendees ?? 1;
        
        $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
            $facility,
            $booking->facility_id,
            $booking->booking_date->format('Y-m-d'),
            $booking->start_time->format('Y-m-d H:i:s'),
            $booking->end_time->format('Y-m-d H:i:s'),
            $expectedAttendees,
            $booking->id
        );
        
        if (!$capacityCheck['available']) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Cannot approve: ' . $capacityCheck['message'],
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 409);
        }

        $booking->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Create status history
        $user = auth()->user();
        $notes = 'Booking approved by ' . ($user->isAdmin() ? 'admin' : 'staff');
        $booking->statusHistory()->create([
            'status' => 'approved',
            'changed_by' => auth()->id(),
            'notes' => $notes,
        ]);

        // Send notification to user
        $this->notificationService->sendBookingNotification($booking, 'approved', 'Your booking has been approved!');

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['user', 'facility', 'approver', 'attendees']),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Reject a booking (Admin only)
     */
    public function reject(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'pending') {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Only pending bookings can be rejected',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 400);
        }

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        // Create status history
        try {
            $user = auth()->user();
            $notes = 'Booking rejected by ' . ($user->isAdmin() ? 'admin' : 'staff') . '. Reason: ' . $request->reason;
            $booking->statusHistory()->create([
                'status' => 'rejected',
                'changed_by' => auth()->id(),
                'notes' => $notes,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to create booking status history: ' . $e->getMessage());
        }

        // Send notification to user
        $this->notificationService->sendBookingNotification($booking, 'rejected', 'Your booking has been rejected. Reason: ' . $request->reason);

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Booking rejected successfully',
            'data' => $booking->load(['user', 'facility', 'attendees']),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Cancel an approved booking (Admin only)
     */
    public function cancel(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'approved') {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'Only approved bookings can be cancelled by admin',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 400);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        // Create status history
        try {
            $user = auth()->user();
            $notes = 'Booking cancelled by ' . ($user->isAdmin() ? 'admin' : 'staff') . '. Reason: ' . $request->reason;
            $booking->statusHistory()->create([
                'status' => 'cancelled',
                'changed_by' => auth()->id(),
                'notes' => $notes,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to create booking status history: ' . $e->getMessage());
        }

        // Send notification to user
        $this->notificationService->sendBookingNotification($booking, 'cancelled', 'Your booking has been cancelled by admin. Reason: ' . $request->reason);

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Booking cancelled successfully',
            'data' => $booking->load(['user', 'facility', 'attendees']),
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Mark booking as completed
     */
    public function markComplete(string $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Check if booking can be marked as completed
            if ($booking->status === 'completed') {
                return response()->json([
                    'status' => 'F', // IFA Standard: F (Fail)
                    'message' => 'Booking is already marked as completed',
                    'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                ], 400);
            }

            if ($booking->status === 'cancelled') {
                return response()->json([
                    'status' => 'F', // IFA Standard: F (Fail)
                    'message' => 'Cannot mark a cancelled booking as completed',
                    'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
                ], 400);
            }

            // Update booking status
            $oldStatus = $booking->status;
            $booking->update([
                'status' => 'completed',
            ]);

            // Create status history
            try {
                $user = auth()->user();
                $notes = 'Booking marked as completed by ' . ($user->isAdmin() ? 'admin' : 'staff');
                $booking->statusHistory()->create([
                    'status' => 'completed',
                    'changed_by' => auth()->id(),
                    'notes' => $notes,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create booking status history: ' . $e->getMessage());
            }

            // Send notification to user
            $this->notificationService->sendBookingNotification($booking, 'completed', 'Your booking has been marked as completed!');

            return response()->json([
                'status' => 'S', // IFA Standard
                'message' => 'Booking marked as completed successfully',
                'data' => $booking->load(['user', 'facility', 'attendees']),
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ]);
        } catch (\Exception $e) {
            \Log::error('Mark complete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'E', // IFA Standard: E (Error)
                'message' => 'Failed to mark booking as completed: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 500);
        }
    }


    /**
     * Get pending bookings for admin dropdown
     */
    public function getPendingBookings(Request $request)
    {
        $limit = $request->get('limit', 10);

        $bookings = Booking::with(['user', 'facility'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'facility_name' => $booking->facility->name ?? 'Unknown',
                    'user_name' => $booking->user->name ?? 'Unknown',
                    'booking_date' => $booking->booking_date->format('Y-m-d'),
                    'start_time' => $booking->start_time->format('H:i'),
                    'end_time' => $booking->end_time->format('H:i'),
                    'purpose' => $booking->purpose,
                    'created_at' => $booking->created_at,
                ];
            });

        $count = Booking::where('status', 'pending')->count();

        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Pending bookings retrieved successfully',
            'data' => [
                'bookings' => $bookings,
                'count' => $count,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }
}

