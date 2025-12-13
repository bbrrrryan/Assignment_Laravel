<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Notification;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['user', 'facility'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(15);
        return response()->json(['data' => $bookings]);
    }

    /**
     * Store a newly created booking
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
                'booking_date' => 'required|date|after:today', // Users can only book from tomorrow onwards
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'purpose' => 'required|string|max:500',
                'expected_attendees' => 'nullable|integer|min:1',
                'special_requirements' => 'nullable',
            ]);

            // Parse and normalize datetime formats
            try {
                $validated['start_time'] = \Carbon\Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s');
                $validated['end_time'] = \Carbon\Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Invalid date/time format. Please use the correct format.',
                    'error' => $e->getMessage(),
                ], 422);
            }

            // Validate that end_time is after start_time
            $startTime = \Carbon\Carbon::parse($validated['start_time']);
            $endTime = \Carbon\Carbon::parse($validated['end_time']);
            
            if ($endTime->lte($startTime)) {
                return response()->json([
                    'message' => 'End time must be after start time',
                ], 422);
            }

        $facility = Facility::findOrFail($validated['facility_id']);
        
        // Check if booking date is within facility's available days
        $bookingDate = \Carbon\Carbon::parse($validated['booking_date']);
        $dayOfWeek = strtolower($bookingDate->format('l')); // e.g., 'monday', 'tuesday'
        
        if ($facility->available_day && is_array($facility->available_day) && !empty($facility->available_day)) {
            // Check if the day of week is in the available days array
            if (!in_array($dayOfWeek, $facility->available_day)) {
                $availableDaysStr = implode(', ', array_map('ucfirst', $facility->available_day));
                return response()->json([
                    'message' => "This facility is not available on {$bookingDate->format('l, F j, Y')}. Available days: {$availableDaysStr}",
                ], 422);
            }
        }
        
        // Get facility available time range (default to 08:00-20:00 if not set)
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
        
        // Validate time range: must be within facility's available time
        $startHour = $startTime->format('H:i');
        $endHour = $endTime->format('H:i');
        
        if ($startHour < $minTime || $startHour > $maxTime) {
            return response()->json([
                'message' => "Start time must be between {$minTime} and {$maxTime} (facility operating hours)",
            ], 422);
        }
        
        if ($endHour < $minTime || $endHour > $maxTime) {
            return response()->json([
                'message' => "End time must be between {$minTime} and {$maxTime} (facility operating hours)",
            ], 422);
        }

        // Check if facility is available
        if ($facility->status !== 'available') {
            return response()->json([
                'message' => 'Facility is not available for booking',
            ], 400);
        }

        // Calculate duration
        $startTime = \Carbon\Carbon::parse($validated['start_time']);
        $endTime = \Carbon\Carbon::parse($validated['end_time']);
        $durationHours = $startTime->diffInHours($endTime);
        
        if ($durationHours <= 0) {
            return response()->json([
                'message' => 'End time must be after start time',
            ], 400);
        }

        // Check max_booking_hours limit for the user on this date
        $maxBookingHours = $facility->max_booking_hours ?? 1;
        $userBookingsOnDate = Booking::where('user_id', auth()->id())
            ->where('facility_id', $validated['facility_id'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->whereIn('status', ['pending', 'approved'])
            ->get();
        
        $totalUserBookingHours = $userBookingsOnDate->sum('duration_hours');
        $totalAfterBooking = $totalUserBookingHours + $durationHours;
        
        if ($totalAfterBooking > $maxBookingHours) {
            return response()->json([
                'message' => "You have reached the maximum booking limit for this facility on this date. Maximum allowed: {$maxBookingHours} hour(s), Your current bookings: {$totalUserBookingHours} hour(s), After this booking: {$totalAfterBooking} hour(s)",
            ], 400);
        }

        // Check capacity - use request input to safely access nullable field
        $expectedAttendees = $request->input('expected_attendees') ?? 1; // Default to 1 if not provided
        if ($expectedAttendees > $facility->capacity) {
            return response()->json([
                'message' => 'Expected attendees exceed facility capacity',
            ], 400);
        }

        // Check capacity for overlapping bookings - check by hour segments
        // This ensures accurate capacity checking for 1-hour and 2-hour bookings
        $capacityCheck = $this->checkCapacityByTimeSegments(
            $facility,
            $validated['facility_id'],
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
            $expectedAttendees
        );
        
        if (!$capacityCheck['available']) {
            return response()->json([
                'message' => $capacityCheck['message'],
            ], 409);
        }

        // Handle special_requirements safely
        $specialRequirements = null;
        if ($request->has('special_requirements') && $request->special_requirements) {
            $req = $request->special_requirements;
            // If it's already an array, use it; if it's a JSON string, decode it
            if (is_string($req)) {
                $decoded = json_decode($req, true);
                $specialRequirements = json_last_error() === JSON_ERROR_NONE ? $decoded : $req;
            } else {
                $specialRequirements = $req;
            }
        }

        // Only students can create bookings
        $user = auth()->user();
        
        if (!$user->isStudent()) {
            return response()->json([
                'message' => 'Only students can create bookings',
            ], 403);
        }
        
        // Students always create pending bookings that require admin/staff approval
        $bookingStatus = 'pending';

        $bookingData = [
            'user_id' => auth()->id(),
            'facility_id' => $validated['facility_id'],
            'booking_number' => 'BK-' . time() . '-' . rand(1000, 9999),
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration_hours' => $durationHours,
            'purpose' => $validated['purpose'],
            'expected_attendees' => $expectedAttendees,
            'special_requirements' => $specialRequirements,
            'status' => $bookingStatus,
        ];

        // If approved by admin, set approved_by and approved_at
        if ($bookingStatus === 'approved') {
            $bookingData['approved_by'] = auth()->id();
            $bookingData['approved_at'] = now();
        }

        $booking = Booking::create($bookingData);

        // Create status history
        try {
            $booking->statusHistory()->create([
                'status' => $booking->status,
                'changed_by' => auth()->id(),
                'notes' => 'Booking created',
            ]);
        } catch (\Exception $e) {
            // Status history is optional, continue even if it fails
            \Log::warning('Failed to create booking status history: ' . $e->getMessage());
        }

        // Send notification to user
        if ($booking->status === 'approved') {
            $this->sendBookingNotification($booking, 'approved', 'Your booking has been created and approved!');
        } else {
            $this->sendBookingNotification($booking, 'pending', 'Your booking has been submitted and is pending approval.');
        }

        return response()->json([
            'message' => 'Booking created successfully',
            'data' => $booking->load(['user', 'facility']),
        ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Booking creation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $booking = Booking::with(['user', 'facility', 'statusHistory'])->findOrFail($id);
            return response()->json(['data' => $booking]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Booking not found',
                'error' => 'No query results for model [App\Models\Booking] ' . $id
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching booking: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading booking details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a booking (Admin can modify any booking, users can only modify their own pending bookings)
     */
    public function update(Request $request, string $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $user = auth()->user();

            // Check permissions: Admin and Staff can modify any booking, users can only modify their own pending bookings
            if (!$user->isAdmin() && !$user->isStaff() && $booking->user_id !== $user->id) {
                return response()->json([
                    'message' => 'You do not have permission to modify this booking',
                ], 403);
            }

            // Users can only modify their own pending bookings (admin/staff can modify any)
            if (!$user->isAdmin() && !$user->isStaff() && $booking->status !== 'pending') {
                return response()->json([
                    'message' => 'You can only modify pending bookings',
                ], 400);
            }

            $validated = $request->validate([
                'facility_id' => 'sometimes|required|exists:facilities,id',
                'booking_date' => 'sometimes|required|date|after:today', // Users can only book from tomorrow onwards
                'start_time' => 'sometimes|required|string',
                'end_time' => 'sometimes|required|string',
                'purpose' => 'sometimes|required|string|max:500',
                'expected_attendees' => 'nullable|integer|min:1',
                'status' => 'sometimes|required|in:pending,approved,rejected,cancelled',
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

            // Get facility (use existing or new one) - needed for time validation
            $facilityId = $validated['facility_id'] ?? $booking->facility_id;
            $facility = Facility::findOrFail($facilityId);
            
            // Get booking date (use existing or updated value)
            $bookingDate = $validated['booking_date'] ?? $booking->booking_date;
            
            // Check if booking date is within facility's available days
            $bookingDateCarbon = \Carbon\Carbon::parse($bookingDate);
            $dayOfWeek = strtolower($bookingDateCarbon->format('l')); // e.g., 'monday', 'tuesday'
            
            if ($facility->available_day && is_array($facility->available_day) && !empty($facility->available_day)) {
                // Check if the day of week is in the available days array
                if (!in_array($dayOfWeek, $facility->available_day)) {
                    $availableDaysStr = implode(', ', array_map('ucfirst', $facility->available_day));
                    return response()->json([
                        'message' => "This facility is not available on {$bookingDateCarbon->format('l, F j, Y')}. Available days: {$availableDaysStr}",
                    ], 422);
                }
            }
            
            // Get facility available time range (default to 08:00-20:00 if not set)
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
                
                // Validate time range: must be within facility's available time
                $startHour = $startTime->format('H:i');
                $endHour = $endTime->format('H:i');
                
                if ($startHour < $minTime || $startHour > $maxTime) {
                    return response()->json([
                        'message' => "Start time must be between {$minTime} and {$maxTime} (facility operating hours)",
                    ], 422);
                }
                
                if ($endHour < $minTime || $endHour > $maxTime) {
                    return response()->json([
                        'message' => "End time must be between {$minTime} and {$maxTime} (facility operating hours)",
                    ], 422);
                }

                $validated['duration_hours'] = $startTime->diffInHours($endTime);
            }
            
            // Validate time range if only start_time is provided
            if (isset($validated['start_time']) && !isset($validated['end_time'])) {
                $startTime = \Carbon\Carbon::parse($validated['start_time']);
                $startHour = $startTime->format('H:i');
                
                if ($startHour < $minTime || $startHour > $maxTime) {
                    return response()->json([
                        'message' => "Start time must be between {$minTime} and {$maxTime} (facility operating hours)",
                    ], 422);
                }
            }
            
            // Validate time range if only end_time is provided
            if (isset($validated['end_time']) && !isset($validated['start_time'])) {
                $endTime = \Carbon\Carbon::parse($validated['end_time']);
                $endHour = $endTime->format('H:i');
                
                if ($endHour < $minTime || $endHour > $maxTime) {
                    return response()->json([
                        'message' => "End time must be between {$minTime} and {$maxTime} (facility operating hours)",
                    ], 422);
                }
            }

            // Get booking details (use existing or updated values)
            $bookingDate = $validated['booking_date'] ?? $booking->booking_date;
            $startTime = $validated['start_time'] ?? $booking->start_time;
            $endTime = $validated['end_time'] ?? $booking->end_time;
            $expectedAttendees = $validated['expected_attendees'] ?? $booking->expected_attendees ?? 1;
            $newStatus = $validated['status'] ?? $booking->status;

            // Check capacity if expected_attendees is provided
            if ($expectedAttendees > $facility->capacity) {
                return response()->json([
                    'message' => 'Expected attendees exceed facility capacity',
                ], 400);
            }

            // Check capacity for overlapping bookings if status is being set to approved
            // or if time/attendees are being changed
            if ($newStatus === 'approved' || isset($validated['start_time']) || isset($validated['end_time']) || isset($validated['expected_attendees'])) {
                // Use hourly segment capacity check for accurate validation
                $capacityCheck = $this->checkCapacityByTimeSegments(
                    $facility,
                    $facilityId,
                    $bookingDate,
                    $startTime,
                    $endTime,
                    $expectedAttendees,
                    $booking->id // Exclude current booking from check
                );
                
                if (!$capacityCheck['available']) {
                    return response()->json([
                        'message' => $capacityCheck['message'],
                    ], 409);
                }
            }

            // Update booking
            $booking->update($validated);

            // Create status history if status changed
            if (isset($validated['status']) && $validated['status'] !== $booking->getOriginal('status')) {
                try {
                    $user = auth()->user();
                    $notes = 'Booking modified by user';
                    if ($user->isAdmin() || $user->isStaff()) {
                        $notes = 'Booking modified by ' . ($user->isAdmin() ? 'admin' : 'staff');
                    }
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
                'data' => $booking->load(['user', 'facility', 'statusHistory']),
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
     * Delete booking - Disabled for admin
     * Admin should use reject or cancel instead
     */
    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Delete functionality is disabled. Please use reject or cancel instead.',
        ], 403);
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

        // Check capacity before approving using hourly segment check
        $facility = $booking->facility;
        $expectedAttendees = $booking->expected_attendees ?? 1;
        
        $capacityCheck = $this->checkCapacityByTimeSegments(
            $facility,
            $booking->facility_id,
            $booking->booking_date->format('Y-m-d'),
            $booking->start_time->format('Y-m-d H:i:s'),
            $booking->end_time->format('Y-m-d H:i:s'),
            $expectedAttendees,
            $booking->id // Exclude current booking from check
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
        $this->sendBookingNotification($booking, 'approved', 'Your booking has been approved!');

        return response()->json([
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['user', 'facility', 'approver']),
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
        $this->sendBookingNotification($booking, 'rejected', 'Your booking has been rejected. Reason: ' . $request->reason);

        return response()->json([
            'message' => 'Booking rejected successfully',
            'data' => $booking->load(['user', 'facility']),
        ]);
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason ?? null,
        ]);

        // Create status history
        try {
            $booking->statusHistory()->create([
                'status' => 'cancelled',
                'changed_by' => auth()->id(),
                'notes' => 'Booking cancelled' . ($request->reason ? '. Reason: ' . $request->reason : ''),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to create booking status history: ' . $e->getMessage());
        }

        // Send notification to user (only if cancelled by admin, not by user themselves)
        if (auth()->user()->isAdmin() || auth()->user()->isStaff()) {
            $this->sendBookingNotification($booking, 'cancelled', 'Your booking has been cancelled' . ($request->reason ? '. Reason: ' . $request->reason : ''));
        }

        return response()->json(['data' => $booking]);
    }

    public function myBookings()
    {
        return response()->json(['data' => auth()->user()->bookings()->with(['user', 'facility'])->get()]);
    }

    /**
     * Check facility availability for booking
     */
    public function checkAvailability(string $facilityId, Request $request)
    {
        $request->validate([
            'date' => 'required|date|after:today', // Users can only book from tomorrow onwards
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
            'expected_attendees' => 'nullable|integer|min:1',
        ]);

        $facility = Facility::findOrFail($facilityId);

        // Check if facility is available
        if ($facility->status !== 'available') {
            return response()->json([
                'is_available' => false,
                'message' => 'Facility is not available',
                'reason' => $facility->status,
            ]);
        }

        // Check if booking date is within facility's available days
        $bookingDate = \Carbon\Carbon::parse($request->date);
        $dayOfWeek = strtolower($bookingDate->format('l')); // e.g., 'monday', 'tuesday'
        
        if ($facility->available_day && is_array($facility->available_day) && !empty($facility->available_day)) {
            // Check if the day of week is in the available days array
            if (!in_array($dayOfWeek, $facility->available_day)) {
                $availableDaysStr = implode(', ', array_map('ucfirst', $facility->available_day));
                return response()->json([
                    'is_available' => false,
                    'message' => "This facility is not available on {$bookingDate->format('l, F j, Y')}. Available days: {$availableDaysStr}",
                    'reason' => 'day_not_available',
                ]);
            }
        }

        // Check capacity for overlapping bookings
        $expectedAttendees = $request->input('expected_attendees') ?? 1;
        
        // Find all pending and approved bookings that overlap with the requested time slot
        // Include pending bookings in capacity count
        $overlappingBookings = Booking::where('facility_id', $facilityId)
            ->whereDate('booking_date', $request->date)
            ->whereIn('status', ['pending', 'approved']) // Include pending and approved
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })
            ->get();

        // Calculate total expected attendees for overlapping bookings
        $totalAttendees = $overlappingBookings->sum(function($booking) {
            return $booking->expected_attendees ?? 1;
        });

        // Check if adding this booking would exceed capacity
        $totalAfterBooking = $totalAttendees + $expectedAttendees;
        $isAvailable = $totalAfterBooking <= $facility->capacity;
        $availableCapacity = max(0, $facility->capacity - $totalAttendees);

        return response()->json([
            'is_available' => $isAvailable,
            'message' => $isAvailable 
                ? 'Time slot is available. Capacity allows this booking.' 
                : 'Time slot capacity would be exceeded. Available capacity: ' . $availableCapacity . ', Requested: ' . $expectedAttendees,
            'data' => [
                'facility_id' => $facilityId,
                'facility_capacity' => $facility->capacity,
                'date' => $request->date,
                'time_range' => [
                    'start' => $request->start_time,
                    'end' => $request->end_time,
                ],
                'expected_attendees' => $expectedAttendees,
                'current_booked_attendees' => $totalAttendees,
                'available_capacity' => $availableCapacity,
                'total_after_booking' => $totalAfterBooking,
                'overlapping_bookings' => $overlappingBookings->map(function($booking) {
                    return [
                        'id' => $booking->id,
                        'start_time' => $booking->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $booking->end_time->format('Y-m-d H:i:s'),
                        'status' => $booking->status,
                        'expected_attendees' => $booking->expected_attendees ?? 1,
                    ];
                }),
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

    /**
     * Check capacity by time segments (hourly) to ensure accurate capacity checking
     * for 1-hour and 2-hour bookings
     * 
     * @param Facility $facility
     * @param int $facilityId
     * @param string $bookingDate
     * @param string $startTime
     * @param string $endTime
     * @param int $expectedAttendees
     * @param int|null $excludeBookingId Exclude this booking ID from the check (for updates)
     * @return array ['available' => bool, 'message' => string]
     */
    private function checkCapacityByTimeSegments(
        Facility $facility,
        int $facilityId,
        string $bookingDate,
        string $startTime,
        string $endTime,
        int $expectedAttendees,
        ?int $excludeBookingId = null
    ): array {
        // Get all pending and approved bookings for this facility on this date
        // Include pending bookings in capacity count
        $query = Booking::where('facility_id', $facilityId)
            ->whereDate('booking_date', $bookingDate)
            ->whereIn('status', ['pending', 'approved']); // Include pending and approved
        
        // Exclude current booking if updating
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }
        
        $bookings = $query->get();

        // Parse the requested time range
        $requestStart = \Carbon\Carbon::parse($startTime);
        $requestEnd = \Carbon\Carbon::parse($endTime);
        
        // Create hourly time segments for the requested time range
        $timeSegments = [];
        $current = $requestStart->copy();
        
        while ($current < $requestEnd) {
            $segmentStart = $current->copy();
            $segmentEnd = $current->copy()->addHour();
            
            // Don't go beyond the requested end time
            if ($segmentEnd > $requestEnd) {
                $segmentEnd = $requestEnd->copy();
            }
            
            $timeSegments[] = [
                'start' => $segmentStart,
                'end' => $segmentEnd,
            ];
            
            $current = $segmentEnd;
        }
        
        // Check each time segment
        foreach ($timeSegments as $segment) {
            $segmentStart = $segment['start'];
            $segmentEnd = $segment['end'];
            
            // Find all bookings that overlap with this segment
            $overlappingBookings = $bookings->filter(function($booking) use ($segmentStart, $segmentEnd) {
                $bookingStart = \Carbon\Carbon::parse($booking->start_time);
                $bookingEnd = \Carbon\Carbon::parse($booking->end_time);
                
                // Check if booking overlaps with this segment
                // Overlap occurs when: bookingStart < segmentEnd AND bookingEnd > segmentStart
                return $bookingStart < $segmentEnd && $bookingEnd > $segmentStart;
            });
            
            // Calculate total attendees in this segment (existing bookings + new booking)
            $totalAttendees = $overlappingBookings->sum(function($booking) {
                return $booking->expected_attendees ?? 1;
            }) + $expectedAttendees;
            
            // Check if this segment would exceed capacity
            if ($totalAttendees > $facility->capacity) {
                $segmentTimeStr = $segmentStart->format('H:i') . ' - ' . $segmentEnd->format('H:i');
                $existingAttendees = $totalAttendees - $expectedAttendees;
                
                return [
                    'available' => false,
                    'message' => "Booking would exceed facility capacity during {$segmentTimeStr}. " .
                                "Capacity: {$facility->capacity}, " .
                                "Current attendees: {$existingAttendees}, " .
                                "After booking: {$totalAttendees}"
                ];
            }
        }
        
        return [
            'available' => true,
            'message' => 'Capacity check passed'
        ];
    }

    /**
     * Send notification to user about booking status change
     */
    private function sendBookingNotification(Booking $booking, string $status, string $message)
    {
        try {
            // Determine notification type based on status
            $type = 'info';
            if ($status === 'approved') {
                $type = 'success';
            } elseif ($status === 'rejected') {
                $type = 'error';
            } elseif ($status === 'cancelled') {
                $type = 'warning';
            }

            // Create notification title
            $title = 'Booking ' . ucfirst($status);
            if ($status === 'approved') {
                $title = 'Booking Approved';
            } elseif ($status === 'rejected') {
                $title = 'Booking Rejected';
            } elseif ($status === 'cancelled') {
                $title = 'Booking Cancelled';
            } elseif ($status === 'pending') {
                $title = 'Booking Submitted';
            }

            // Create detailed message
            $facilityName = $booking->facility->name ?? 'Facility';
            $bookingDate = $booking->booking_date->format('Y-m-d');
            $startTime = $booking->start_time->format('H:i');
            $endTime = $booking->end_time->format('H:i');
            
            $detailedMessage = $message . "\n\n";
            $detailedMessage .= "Facility: {$facilityName}\n";
            $detailedMessage .= "Date: {$bookingDate}\n";
            $detailedMessage .= "Time: {$startTime} - {$endTime}\n";
            $detailedMessage .= "Booking Number: {$booking->booking_number}";

            // Create notification
            $notification = Notification::create([
                'title' => $title,
                'message' => $detailedMessage,
                'type' => $type,
                'priority' => 'medium',
                'created_by' => auth()->id(),
                'target_audience' => 'specific',
                'target_user_ids' => [$booking->user_id],
                'is_active' => true,
            ]);

            // Send notification to user
            $notification->users()->sync([
                $booking->user_id => [
                    'is_read' => false,
                    'is_acknowledged' => false,
                ]
            ]);

            // Update scheduled_at
            $notification->update(['scheduled_at' => now()]);

        } catch (\Exception $e) {
            // Log error but don't fail the booking operation
            \Log::warning('Failed to send booking notification: ' . $e->getMessage());
        }
    }
}
