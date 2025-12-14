<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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
        
        $bookings = Booking::with(['user', 'facility', 'rescheduleProcessor'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->facility_id, fn($q) => $q->where('facility_id', $request->facility_id))
            ->when($request->reschedule_status, fn($q) => $q->where('reschedule_status', $request->reschedule_status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        return response()->json(['data' => $bookings]);
    }

    /**
     * Update a booking (Admin can modify any booking)
     */
    public function update(Request $request, string $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $user = auth()->user();

            $validated = $request->validate([
                'facility_id' => 'sometimes|required|exists:facilities,id',
                'booking_date' => 'sometimes|required|date',
                'start_time' => 'sometimes|required|string',
                'end_time' => 'sometimes|required|string',
                'purpose' => 'sometimes|required|string|max:500',
                'expected_attendees' => 'nullable|integer|min:1',
                'status' => 'sometimes|required|in:pending,approved,rejected,cancelled',
                'attendees_passports' => 'nullable|array',
                'attendees_passports.*' => 'nullable|string|max:255',
            ]);

            // Parse datetime if provided
            if (isset($validated['start_time'])) {
                try {
                    $validated['start_time'] = \Carbon\Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Invalid start_time format',
                        'error' => $e->getMessage(),
                    ], 422);
                }
            }

            if (isset($validated['end_time'])) {
                try {
                    $validated['end_time'] = \Carbon\Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Invalid end_time format',
                        'error' => $e->getMessage(),
                    ], 422);
                }
            }

            // Get facility (use existing or new one)
            $facilityId = $validated['facility_id'] ?? $booking->facility_id;
            $facility = Facility::findOrFail($facilityId);
            
            // Get booking date
            $bookingDate = $validated['booking_date'] ?? $booking->booking_date;
            
            // Check if booking date is within facility's available days
            $bookingDateCarbon = \Carbon\Carbon::parse($bookingDate);
            $dayOfWeek = strtolower($bookingDateCarbon->format('l'));
            
            if ($facility->available_day && is_array($facility->available_day) && !empty($facility->available_day)) {
                if (!in_array($dayOfWeek, $facility->available_day)) {
                    $availableDaysStr = implode(', ', array_map('ucfirst', $facility->available_day));
                    return response()->json([
                        'message' => "This facility is not available on {$bookingDateCarbon->format('l, F j, Y')}. Available days: {$availableDaysStr}",
                    ], 422);
                }
            }
            
            // Get facility available time range
            $minTime = '08:00';
            $maxTime = '20:00';
            if ($facility->available_time && is_array($facility->available_time)) {
                if (isset($facility->available_time['start']) && !empty($facility->available_time['start'])) {
                    $minTime = $facility->available_time['start'];
                }
                if (isset($facility->available_time['end']) && !empty($facility->available_time['end'])) {
                    $maxTime = $facility->available_time['end'];
                }
            }
            
            // Validate time range if both times are provided
            if (isset($validated['start_time']) && isset($validated['end_time'])) {
                $startTime = \Carbon\Carbon::parse($validated['start_time']);
                $endTime = \Carbon\Carbon::parse($validated['end_time']);
                
                if ($endTime->lte($startTime)) {
                    return response()->json([
                        'message' => 'End time must be after start time',
                    ], 422);
                }
                
                $startHour = $startTime->format('H:i');
                $endHour = $endTime->format('H:i');
                
                if ($startHour < $minTime || $startHour > $maxTime) {
                    return response()->json([
                        'message' => "Start time must be between {$minTime} and {$maxTime}",
                    ], 422);
                }
                
                if ($endHour < $minTime || $endHour > $maxTime) {
                    return response()->json([
                        'message' => "End time must be between {$minTime} and {$maxTime}",
                    ], 422);
                }

                $validated['duration_hours'] = $startTime->diffInHours($endTime);
            }
            
            // Handle expected_attendees
            $expectedAttendees = $request->input('expected_attendees');
            if (!$facility->enable_multi_attendees) {
                $expectedAttendees = 1;
            } else {
                $expectedAttendees = $expectedAttendees ?? $booking->expected_attendees ?? 1;
                
                if ($facility->max_attendees && $expectedAttendees > $facility->max_attendees) {
                    return response()->json([
                        'message' => "Expected attendees ({$expectedAttendees}) exceed maximum allowed ({$facility->max_attendees})",
                    ], 400);
                }
            }
            
            if ($expectedAttendees > $facility->capacity) {
                return response()->json([
                    'message' => 'Expected attendees exceed facility capacity',
                ], 400);
            }
            
            $validated['expected_attendees'] = $expectedAttendees;

            // Get booking details
            $bookingDate = $validated['booking_date'] ?? $booking->booking_date;
            $startTime = $validated['start_time'] ?? $booking->start_time;
            $endTime = $validated['end_time'] ?? $booking->end_time;
            $newStatus = $validated['status'] ?? $booking->status;

            // Check capacity if status is being set to approved or if time/attendees are being changed
            if ($newStatus === 'approved' || isset($validated['start_time']) || isset($validated['end_time']) || isset($validated['expected_attendees'])) {
                $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
                    $facility,
                    $facilityId,
                    $bookingDate,
                    $startTime,
                    $endTime,
                    $expectedAttendees,
                    $booking->id
                );
                
                if (!$capacityCheck['available']) {
                    return response()->json([
                        'message' => $capacityCheck['message'],
                    ], 409);
                }
            }

            // Update booking
            $booking->update($validated);

            // Update attendees if provided
            if ($request->has('attendees_passports') && is_array($request->attendees_passports)) {
                $booking->attendees()->delete();
                
                foreach ($request->attendees_passports as $passport) {
                    if (!empty(trim($passport))) {
                        $booking->attendees()->create([
                            'student_passport' => trim($passport),
                        ]);
                    }
                }
            }

            // Create status history if status changed
            if (isset($validated['status']) && $validated['status'] !== $booking->getOriginal('status')) {
                try {
                    $notes = 'Booking modified by ' . ($user->isAdmin() ? 'admin' : 'staff');
                    $booking->statusHistory()->create([
                        'status' => $validated['status'],
                        'changed_by' => auth()->id(),
                        'notes' => $notes,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to create booking status history: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Booking updated successfully',
                'data' => $booking->load(['user', 'facility', 'statusHistory', 'attendees']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Booking update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update booking: ' . $e->getMessage(),
            ], 500);
        }
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
     * Approve reschedule request
     */
    public function approveReschedule(string $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Check if there's a pending reschedule request
            if ($booking->reschedule_status !== 'pending') {
                return response()->json([
                    'message' => 'No pending reschedule request found for this booking',
                ], 400);
            }

            // Get facility
            $facility = $booking->facility;
            $expectedAttendees = $booking->expected_attendees ?? 1;

            // Check capacity for requested time
            $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
                $facility,
                $facility->id,
                $booking->requested_booking_date->format('Y-m-d'),
                $booking->requested_start_time->format('Y-m-d H:i:s'),
                $booking->requested_end_time->format('Y-m-d H:i:s'),
                $expectedAttendees,
                $booking->id // Exclude current booking from check
            );
            
            if (!$capacityCheck['available']) {
                return response()->json([
                    'message' => 'Cannot approve reschedule: ' . $capacityCheck['message'],
                ], 409);
            }

            // Calculate new duration
            $startTime = \Carbon\Carbon::parse($booking->requested_start_time);
            $endTime = \Carbon\Carbon::parse($booking->requested_end_time);
            $durationHours = $startTime->diffInHours($endTime);

            // Update booking with new schedule
            $booking->update([
                'booking_date' => $booking->requested_booking_date,
                'start_time' => $booking->requested_start_time,
                'end_time' => $booking->requested_end_time,
                'duration_hours' => $durationHours,
                'reschedule_status' => 'approved',
                'reschedule_processed_by' => auth()->id(),
                'reschedule_processed_at' => now(),
                'requested_booking_date' => null,
                'requested_start_time' => null,
                'requested_end_time' => null,
            ]);

            // Create status history
            try {
                $user = auth()->user();
                $notes = 'Reschedule request approved by ' . ($user->isAdmin() ? 'admin' : 'staff');
                $booking->statusHistory()->create([
                    'status' => $booking->status,
                    'changed_by' => auth()->id(),
                    'notes' => $notes,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create booking status history: ' . $e->getMessage());
            }

            // Send notification to user
            $this->notificationService->sendBookingNotification($booking, 'approved', 'Your reschedule request has been approved!');

            return response()->json([
                'message' => 'Reschedule request approved successfully',
                'data' => $booking->load(['user', 'facility', 'attendees', 'rescheduleProcessor']),
            ]);
        } catch (\Exception $e) {
            \Log::error('Approve reschedule error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to approve reschedule request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject reschedule request
     */
    public function rejectReschedule(Request $request, string $id)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $booking = Booking::findOrFail($id);

            // Check if there's a pending reschedule request
            if ($booking->reschedule_status !== 'pending') {
                return response()->json([
                    'message' => 'No pending reschedule request found for this booking',
                ], 400);
            }

            // Update booking
            $booking->update([
                'reschedule_status' => 'rejected',
                'reschedule_processed_by' => auth()->id(),
                'reschedule_processed_at' => now(),
                'reschedule_rejection_reason' => $request->reason,
                'requested_booking_date' => null,
                'requested_start_time' => null,
                'requested_end_time' => null,
            ]);

            // Create status history
            try {
                $user = auth()->user();
                $notes = 'Reschedule request rejected by ' . ($user->isAdmin() ? 'admin' : 'staff') . '. Reason: ' . $request->reason;
                $booking->statusHistory()->create([
                    'status' => $booking->status,
                    'changed_by' => auth()->id(),
                    'notes' => $notes,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create booking status history: ' . $e->getMessage());
            }

            // Send notification to user
            $this->notificationService->sendBookingNotification($booking, 'rejected', 'Your reschedule request has been rejected. Reason: ' . $request->reason);

            return response()->json([
                'message' => 'Reschedule request rejected successfully',
                'data' => $booking->load(['user', 'facility', 'attendees', 'rescheduleProcessor']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Reject reschedule error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to reject reschedule request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending reschedule requests
     */
    public function getPendingRescheduleRequests(Request $request)
    {
        $limit = $request->get('limit', 10);

        $bookings = Booking::with(['user', 'facility'])
            ->where('reschedule_status', 'pending')
            ->orderBy('reschedule_requested_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'facility_name' => $booking->facility->name ?? 'Unknown',
                    'user_name' => $booking->user->name ?? 'Unknown',
                    'current_booking_date' => $booking->booking_date->format('Y-m-d'),
                    'current_start_time' => $booking->start_time->format('H:i'),
                    'current_end_time' => $booking->end_time->format('H:i'),
                    'requested_booking_date' => $booking->requested_booking_date->format('Y-m-d'),
                    'requested_start_time' => $booking->requested_start_time->format('H:i'),
                    'requested_end_time' => $booking->requested_end_time->format('H:i'),
                    'reschedule_reason' => $booking->reschedule_reason,
                    'reschedule_requested_at' => $booking->reschedule_requested_at,
                ];
            });

        $count = Booking::where('reschedule_status', 'pending')->count();

        return response()->json([
            'message' => 'Pending reschedule requests retrieved successfully',
            'data' => [
                'bookings' => $bookings,
                'count' => $count,
            ],
        ]);
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
                    'booking_number' => $booking->booking_number,
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

