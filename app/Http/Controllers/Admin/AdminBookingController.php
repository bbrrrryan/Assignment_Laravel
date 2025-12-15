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
        
        return response()->json(['data' => $bookings]);
    }


    /**
     * Approve a booking (Admin only)
     */
    public function approve(string $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be approved',
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
                'message' => 'Cannot approve: ' . $capacityCheck['message'],
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
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['user', 'facility', 'approver', 'attendees']),
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
                'message' => 'Only pending bookings can be rejected',
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
            'message' => 'Booking rejected successfully',
            'data' => $booking->load(['user', 'facility', 'attendees']),
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
                    'message' => 'Booking is already marked as completed',
                ], 400);
            }

            if ($booking->status === 'cancelled') {
                return response()->json([
                    'message' => 'Cannot mark a cancelled booking as completed',
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
                'message' => 'Booking marked as completed successfully',
                'data' => $booking->load(['user', 'facility', 'attendees']),
            ]);
        } catch (\Exception $e) {
            \Log::error('Mark complete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to mark booking as completed: ' . $e->getMessage(),
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
            'message' => 'Pending bookings retrieved successfully',
            'data' => [
                'bookings' => $bookings,
                'count' => $count,
            ],
        ]);
    }
}

