<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Booking;
use App\Models\Facility;
use App\Services\BookingValidationService;
use App\Services\BookingCapacityService;
use App\Services\BookingNotificationService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $validationService;
    protected $capacityService;
    protected $notificationService;

    public function __construct(
        BookingValidationService $validationService,
        BookingCapacityService $capacityService,
        BookingNotificationService $notificationService
    ) {
        $this->validationService = $validationService;
        $this->capacityService = $capacityService;
        $this->notificationService = $notificationService;
    }
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
            // Get facility first to check enable_multi_attendees setting
            $facilityId = $request->input('facility_id');
            if (!$facilityId) {
                return response()->json([
                    'message' => 'Facility ID is required',
                ], 422);
            }
            
            $facility = Facility::find($facilityId);
            if (!$facility) {
                return response()->json([
                    'message' => 'Facility not found',
                ], 404);
            }
            
            // Validate request using service
            $validated = $request->validate($this->validationService->getValidationRules($facility));

            // Normalize expected_attendees
            $validated['expected_attendees'] = $this->validationService->normalizeExpectedAttendees(
                $validated['expected_attendees'] ?? null,
                $facility
            );

            // Parse and normalize datetime formats
            try {
                $validated['start_time'] = $this->validationService->parseDateTime($validated['start_time']);
                $validated['end_time'] = $this->validationService->parseDateTime($validated['end_time']);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Invalid date/time format. Please use the correct format.',
                    'error' => $e->getMessage(),
                ], 422);
            }

            // Validate time range
            if ($error = $this->validationService->validateTimeRange($validated['start_time'], $validated['end_time'])) {
                return response()->json(['message' => $error], 422);
            }

            // Validate available day
            if ($error = $this->validationService->validateAvailableDay($validated['booking_date'], $facility)) {
                return response()->json(['message' => $error], 422);
            }

            // Validate available time
            if ($error = $this->validationService->validateAvailableTime(
                $validated['start_time'],
                $validated['end_time'],
                $facility
            )) {
                return response()->json(['message' => $error], 422);
            }

            // Validate facility status
            if ($error = $this->validationService->validateFacilityStatus($facility)) {
                return response()->json(['message' => $error], 400);
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

        // Calculate duration
        $startTime = \Carbon\Carbon::parse($validated['start_time']);
        $endTime = \Carbon\Carbon::parse($validated['end_time']);
        $durationHours = $startTime->diffInHours($endTime);
        
        if ($durationHours <= 0) {
            return response()->json([
                'message' => 'End time must be after start time',
            ], 400);
        }

        // Check max_booking_hours limit
        $maxBookingHours = $facility->max_booking_hours ?? 1;
        $maxHoursCheck = $this->capacityService->checkMaxBookingHours(
            auth()->id(),
            $validated['facility_id'],
            $validated['booking_date'],
            $durationHours,
            $maxBookingHours
        );
        
        if (!$maxHoursCheck['available']) {
            return response()->json(['message' => $maxHoursCheck['message']], 400);
        }

        // Check capacity and multi-attendees setting
        $expectedAttendees = $validated['expected_attendees'];
        
        // Validate capacity
        if ($error = $this->validationService->validateCapacity($expectedAttendees, $facility)) {
            return response()->json(['message' => $error], 400);
        }

        // Check capacity for overlapping bookings
        $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
            $facility,
            $validated['facility_id'],
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
            $expectedAttendees
        );
        
        if (!$capacityCheck['available']) {
            return response()->json(['message' => $capacityCheck['message']], 409);
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

        // Save attendees if provided
        if ($request->has('attendees_passports') && is_array($request->attendees_passports)) {
            foreach ($request->attendees_passports as $passport) {
                if (!empty(trim($passport))) {
                    $booking->attendees()->create([
                        'student_passport' => trim($passport),
                    ]);
                }
            }
        }

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
            $this->notificationService->sendBookingNotification($booking, 'approved', 'Your booking has been created and approved!');
        } else {
            $this->notificationService->sendBookingNotification($booking, 'pending', 'Your booking has been submitted and is pending approval.');
        }

        return response()->json([
            'message' => 'Booking created successfully',
            'data' => $booking->load(['user', 'facility', 'attendees']),
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
            $booking = Booking::with(['user', 'facility', 'statusHistory', 'attendees', 'rescheduleProcessor'])->findOrFail($id);
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
            
            // Handle expected_attendees based on facility's enable_multi_attendees setting
            $expectedAttendees = $request->input('expected_attendees');
            if (!$facility->enable_multi_attendees) {
                // If facility doesn't enable multi-attendees, always use 1
                $expectedAttendees = 1;
            } else {
                // If multi-attendees is enabled, use provided value or keep existing
                $expectedAttendees = $expectedAttendees ?? $booking->expected_attendees ?? 1;
                
                // Check against max_attendees if set
                if ($facility->max_attendees && $expectedAttendees > $facility->max_attendees) {
                    return response()->json([
                        'message' => "Expected attendees ({$expectedAttendees}) exceed maximum allowed ({$facility->max_attendees}) for this facility",
                    ], 400);
                }
            }
            
            $newStatus = $validated['status'] ?? $booking->status;

            // Always check against facility capacity
            if ($expectedAttendees > $facility->capacity) {
                return response()->json([
                    'message' => 'Expected attendees exceed facility capacity',
                ], 400);
            }
            
            // Update validated array with the correct expected_attendees value
            $validated['expected_attendees'] = $expectedAttendees;

            // Check capacity for overlapping bookings if status is being set to approved
            // or if time/attendees are being changed
            if ($newStatus === 'approved' || isset($validated['start_time']) || isset($validated['end_time']) || isset($validated['expected_attendees'])) {
                // Use hourly segment capacity check for accurate validation
                $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
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

            // Update attendees if provided
            if ($request->has('attendees_passports') && is_array($request->attendees_passports)) {
                // Delete existing attendees
                $booking->attendees()->delete();
                
                // Create new attendees
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
        
        $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
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
            $this->notificationService->sendBookingNotification($booking, 'cancelled', 'Your booking has been cancelled' . ($request->reason ? '. Reason: ' . $request->reason : ''));
        }

        return response()->json(['data' => $booking]);
    }

    public function myBookings()
    {
        return response()->json(['data' => auth()->user()->bookings()->with(['user', 'facility', 'attendees', 'rescheduleProcessor'])->get()]);
    }

    /**
     * Submit reschedule request for a booking
     */
    public function requestReschedule(Request $request, string $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $user = auth()->user();

            // Check if user owns this booking
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'message' => 'You do not have permission to reschedule this booking',
                ], 403);
            }

            // Check if booking can be rescheduled (must be approved or pending)
            if (!in_array($booking->status, ['pending', 'approved'])) {
                return response()->json([
                    'message' => 'Only pending or approved bookings can be rescheduled',
                ], 400);
            }

            // Check if there's already a pending reschedule request
            if ($booking->reschedule_status === 'pending') {
                return response()->json([
                    'message' => 'You already have a pending reschedule request for this booking',
                ], 400);
            }

            // Validate request
            $validated = $request->validate([
                'requested_booking_date' => 'required|date|after:today',
                'requested_start_time' => 'required|string',
                'requested_end_time' => 'required|string',
                'reschedule_reason' => 'required|string|max:500',
            ]);

            // Get facility
            $facility = $booking->facility;

            // Parse datetime
            try {
                $requestedStartTime = \Carbon\Carbon::parse($validated['requested_start_time'])->format('Y-m-d H:i:s');
                $requestedEndTime = \Carbon\Carbon::parse($validated['requested_end_time'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Invalid date/time format',
                    'error' => $e->getMessage(),
                ], 422);
            }

            // Validate time range
            $startTime = \Carbon\Carbon::parse($requestedStartTime);
            $endTime = \Carbon\Carbon::parse($requestedEndTime);
            
            if ($endTime->lte($startTime)) {
                return response()->json([
                    'message' => 'End time must be after start time',
                ], 422);
            }

            // Check if booking date is within facility's available days
            $bookingDateCarbon = \Carbon\Carbon::parse($validated['requested_booking_date']);
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

            // Validate time range
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

            // Check capacity for requested time
            $expectedAttendees = $booking->expected_attendees ?? 1;
            $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
                $facility,
                $facility->id,
                $validated['requested_booking_date'],
                $requestedStartTime,
                $requestedEndTime,
                $expectedAttendees,
                $booking->id // Exclude current booking from check
            );
            
            if (!$capacityCheck['available']) {
                return response()->json([
                    'message' => 'Cannot reschedule: ' . $capacityCheck['message'],
                ], 409);
            }

            // Update booking with reschedule request
            $booking->update([
                'reschedule_status' => 'pending',
                'requested_booking_date' => $validated['requested_booking_date'],
                'requested_start_time' => $requestedStartTime,
                'requested_end_time' => $requestedEndTime,
                'reschedule_reason' => $validated['reschedule_reason'],
                'reschedule_requested_at' => now(),
            ]);

            // Create status history
            try {
                $booking->statusHistory()->create([
                    'status' => $booking->status,
                    'changed_by' => auth()->id(),
                    'notes' => 'Reschedule request submitted. Reason: ' . $validated['reschedule_reason'],
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create booking status history: ' . $e->getMessage());
            }

            // Send notification to admin
            $this->notificationService->sendBookingNotification($booking, 'pending', 'A reschedule request has been submitted for booking #' . $booking->booking_number);

            return response()->json([
                'message' => 'Reschedule request submitted successfully',
                'data' => $booking->load(['user', 'facility', 'attendees']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Reschedule request error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to submit reschedule request: ' . $e->getMessage(),
            ], 500);
        }
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
        // Handle expected_attendees based on facility's enable_multi_attendees setting
        $expectedAttendees = $request->input('expected_attendees');
        if (!$facility->enable_multi_attendees) {
            // If facility doesn't enable multi-attendees, always use 1
            $expectedAttendees = 1;
        } else {
            // If multi-attendees is enabled, use provided value or default to 1
            $expectedAttendees = $expectedAttendees ?? 1;
            
            // Check against max_attendees if set
            if ($facility->max_attendees && $expectedAttendees > $facility->max_attendees) {
                return response()->json([
                    'is_available' => false,
                    'message' => "Expected attendees ({$expectedAttendees}) exceed maximum allowed ({$facility->max_attendees}) for this facility",
                    'reason' => 'max_attendees_exceeded',
                ]);
            }
        }
        
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
        // If facility has enable_multi_attendees, each booking occupies the full capacity
        $totalAttendees = $overlappingBookings->sum(function($booking) use ($facility) {
            // If this facility has enable_multi_attendees, each booking occupies full capacity
            if ($facility->enable_multi_attendees) {
                return $facility->capacity;
            }
            // Otherwise, use expected_attendees
            return $booking->expected_attendees ?? 1;
        });

        // For the new booking, if facility has enable_multi_attendees, it occupies full capacity
        $newBookingAttendees = $facility->enable_multi_attendees 
            ? $facility->capacity 
            : $expectedAttendees;

        // Check if adding this booking would exceed capacity
        // If multi_attendees is enabled, only one booking per time slot is allowed
        if ($facility->enable_multi_attendees) {
            $isAvailable = $overlappingBookings->count() === 0;
            $totalAfterBooking = $isAvailable ? $facility->capacity : $facility->capacity;
        } else {
            $totalAfterBooking = $totalAttendees + $newBookingAttendees;
            $isAvailable = $totalAfterBooking <= $facility->capacity;
        }
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

}
