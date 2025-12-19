<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Facility;
use App\Factories\BookingFactory;
use App\Services\BookingValidationService;
use App\Services\BookingCapacityService;
use App\Services\BookingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

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
        $perPage = $request->input('per_page', 15);
        
        $query = Booking::with(['user', 'facility', 'slots'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->facility_id, fn($q) => $q->where('facility_id', $request->facility_id))
            ->when($request->search, function($q) use ($request) {
                $search = $request->search;
                $q->where(function($query) use ($search) {
                    $query->where('purpose', 'like', "%{$search}%")
                        ->orWhereHas('user', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('facility', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            });
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if ($sortBy === 'date') {
            $query->orderBy('booking_date', $sortOrder);
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $bookings = $query->paginate($perPage);
        return response()->json([
            'status' => 'S', 
            'data' => $bookings,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
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
                    'status' => 'F', 
                    'message' => 'Facility ID is required',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 422);
            }
            
            //Service Consumption: Get facility info via HTTP from Facility Management Module
            $baseUrl = config('app.url', 'http://localhost:8000');
            $apiUrl = rtrim($baseUrl, '/') . '/api/facilities/service/get-info';
            
            $facilityResponse = Http::timeout(10)->post($apiUrl, [
                'facility_id' => $facilityId,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ]);
            
            if (!$facilityResponse->successful()) {
                Log::warning('Failed to get facility from Facility Management Module', [
                    'status' => $facilityResponse->status(),
                    'response' => $facilityResponse->body(),
                ]);
                // Fallback to direct query
                $facility = Facility::find($facilityId);
            } else {
                $facilityData = $facilityResponse->json();
                if ($facilityData['status'] === 'S' && isset($facilityData['data']['facility'])) {
                    // Convert array to Facility model instance
                    $facility = Facility::find($facilityId);
                } else {
                    $facility = Facility::find($facilityId);
                }
            }
            
            if (!$facility) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Facility not found',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 404);
            }
            
            // Check if time_slots array is provided (new format) or use old format
            $timeSlots = $request->input('time_slots', []);
            $useTimeSlots = !empty($timeSlots) && is_array($timeSlots);
            
            if ($useTimeSlots) {
                // New format: validate time_slots array
                try {
                    $request->validate([
                        'time_slots' => 'required|array|min:1',
                        'time_slots.*.date' => 'required|date_format:Y-m-d',
                        'time_slots.*.start_time' => 'required|string',
                        'time_slots.*.end_time' => 'required|string',
                    ]);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    Log::error('Time slots validation failed', [
                        'errors' => $e->errors(),
                        'time_slots' => $timeSlots,
                    ]);
                    throw $e;
                }
                
                // Parse and validate each slot
                $parsedSlots = [];
                $totalDuration = 0;
                $bookingDate = null;
                
                foreach ($timeSlots as $slot) {
                    try {
                        $slotDate = $slot['date'];
                        $slotStart = \Carbon\Carbon::parse($slot['start_time']);
                        $slotEnd = \Carbon\Carbon::parse($slot['end_time']);
                        
                        if ($slotEnd->lte($slotStart)) {
                            return response()->json([
                                'status' => 'F', 
                                'message' => 'End time must be after start time for slot',
                                'timestamp' => now()->format('Y-m-d H:i:s'), 
                            ], 422);
                        }
                        
                        $duration = $slotStart->diffInHours($slotEnd);
                        $totalDuration += $duration;
                        
                        if (!$bookingDate) {
                            $bookingDate = $slotDate;
                        }
                        
                        $parsedSlots[] = [
                            'date' => $slotDate,
                            'start_time' => $slotStart->format('Y-m-d H:i:s'),
                            'end_time' => $slotEnd->format('Y-m-d H:i:s'),
                            'duration' => $duration,
                        ];
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'F', 
                            'message' => 'Invalid time slot format: ' . $e->getMessage(),
                            'timestamp' => now()->format('Y-m-d H:i:s'), 
                        ], 422);
                    }
                }
                
                // Validate available day for first slot
                if ($error = $this->validationService->validateAvailableDay($bookingDate, $facility)) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }
                
                // Validate each slot's available time
                foreach ($parsedSlots as $slot) {
                    if ($error = $this->validationService->validateAvailableTime(
                        $slot['start_time'],
                        $slot['end_time'],
                        $facility
                    )) {
                        return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                    }
                }
                
                // Get validation rules based on facility settings
                $expectedAttendeesRule = 'nullable|integer|min:1';
                if ($facility->enable_multi_attendees) {
                    $expectedAttendeesRule = 'required|integer|min:1';
                    if ($facility->max_attendees) {
                        $expectedAttendeesRule .= '|max:' . $facility->max_attendees;
                    }
                }
                
                $validated = $request->validate([
                    'facility_id' => 'required|exists:facilities,id',
                    'purpose' => 'required|string|max:500',
                    'expected_attendees' => $expectedAttendeesRule,
                    'attendees_passports' => 'nullable|array',
                    'attendees_passports.*' => [
                        'nullable',
                        'string',
                        'max:255',
                        function ($attribute, $value, $fail) {
                            if (!empty($value)) {
                                $trimmedValue = trim($value);
                                if (!empty($trimmedValue) && !$this->validationService->validateStudentIdFormat($trimmedValue)) {
                                    $fail('The attendee passport must be in the format YYWMR##### (e.g., 25WMR00001).');
                                }
                            }
                        },
                    ],
                ]);
                
                $validated['booking_date'] = $bookingDate;
                $validated['start_time'] = $parsedSlots[0]['start_time'];
                $validated['end_time'] = $parsedSlots[count($parsedSlots) - 1]['end_time'];
                $validated['duration_hours'] = $totalDuration;
            } else {
                // Old format: use existing validation
                $validated = $request->validate($this->validationService->getValidationRules($facility));

                // Parse and normalize datetime formats
                try {
                    $validated['start_time'] = $this->validationService->parseDateTime($validated['start_time']);
                    $validated['end_time'] = $this->validationService->parseDateTime($validated['end_time']);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => 'Invalid date/time format. Please use the correct format.',
                        'error' => $e->getMessage(),
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }

                // Validate time range
                if ($error = $this->validationService->validateTimeRange($validated['start_time'], $validated['end_time'])) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }

                // Validate available day
                if ($error = $this->validationService->validateAvailableDay($validated['booking_date'], $facility)) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }

                // Validate available time
                if ($error = $this->validationService->validateAvailableTime(
                    $validated['start_time'],
                    $validated['end_time'],
                    $facility
                )) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }
                
                // Create single slot for old format
                $parsedSlots = [[
                    'date' => $validated['booking_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'duration' => \Carbon\Carbon::parse($validated['start_time'])->diffInHours(\Carbon\Carbon::parse($validated['end_time'])),
                ]];
            }

            // Normalize expected_attendees
            $validated['expected_attendees'] = $this->validationService->normalizeExpectedAttendees(
                $validated['expected_attendees'] ?? null,
                $facility
            );

            // Validate facility status
            if ($error = $this->validationService->validateFacilityStatus($facility)) {
                return response()->json([
                    'status' => 'F', 
                    'message' => $error,
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 400);
            }

        // Calculate total duration from slots
        $durationHours = $validated['duration_hours'] ?? 0;
        
        if ($durationHours <= 0) {
            return response()->json([
                'status' => 'F', 
                'message' => 'Invalid booking duration',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
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
            return response()->json([
                'status' => 'F', 
                'message' => $maxHoursCheck['message'],
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

        // Check capacity and multi-attendees setting
        $expectedAttendees = $validated['expected_attendees'];
        
        // Validate capacity
        if ($error = $this->validationService->validateCapacity($expectedAttendees, $facility)) {
            return response()->json([
                'status' => 'F', 
                'message' => $error,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

        // Security: Use database transaction with row-level locking to prevent race conditions
        // This ensures atomic capacity check and booking creation
        return DB::transaction(function () use ($parsedSlots, $validated, $facility, $expectedAttendees, $request) {
            // Lock facility row to prevent concurrent modifications
            $facility = Facility::lockForUpdate()->findOrFail($validated['facility_id']);
            
            // Re-check capacity for each slot within transaction (TOCTOU protection)
            foreach ($parsedSlots as $slot) {
                $capacityCheck = $this->capacityService->checkCapacityByTimeSegments(
                    $facility,
                    $validated['facility_id'],
                    $slot['date'],
                    $slot['start_time'],
                    $slot['end_time'],
                    $expectedAttendees
                );
                
                if (!$capacityCheck['available']) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $capacityCheck['message'],
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 409);
                }
            }

            // Only students and staff can create bookings (not admin)
            $user = auth()->user();
            
            if ($user->isAdmin()) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Admins cannot create bookings through this endpoint. Please use the admin panel.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 403);
            }
            
            // Only students are restricted to sports or library facilities
            // Staff can book all facility types
            if ($user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Students can only book sports or library facilities.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 403);
            }
            
            // Students always create pending bookings that require admin/staff approval
            $bookingStatus = 'pending';

            // Extract time portion from start_time and end_time if they are datetime strings
            $startTime = $validated['start_time'];
            $endTime = $validated['end_time'];
            
            // If start_time is a datetime string, extract just the time portion (H:i)
            if (strlen($startTime) > 5 && strpos($startTime, ' ') !== false) {
                $startTime = \Carbon\Carbon::parse($startTime)->format('H:i');
            }
            
            // If end_time is a datetime string, extract just the time portion (H:i)
            if (strlen($endTime) > 5 && strpos($endTime, ' ') !== false) {
                $endTime = \Carbon\Carbon::parse($endTime)->format('H:i');
            }

            // Use BookingFactory to create the booking
            $booking = BookingFactory::makeBooking(
                userId: auth()->id(),
                facilityId: $validated['facility_id'],
                bookingDate: $validated['booking_date'],
                startTime: $startTime,
                endTime: $endTime,
                purpose: $validated['purpose'],
                status: $bookingStatus,
                expectedAttendees: $expectedAttendees
            );

            // Log activity for user who created the booking
            try {
                $user->activityLogs()->create([
                    'action' => 'create_booking',
                    'description' => "Created booking for facility: {$facility->name} on {$validated['booking_date']}",
                ]);
            } catch (\Exception $e) {
                // Activity log is optional, continue even if it fails
                Log::warning('Failed to create booking activity log: ' . $e->getMessage());
            }

            // Create booking slots
            foreach ($parsedSlots as $slot) {
                $slotStart = \Carbon\Carbon::parse($slot['start_time']);
                $slotEnd = \Carbon\Carbon::parse($slot['end_time']);
                
                BookingSlot::create([
                    'booking_id' => $booking->id,
                    'slot_date' => $slot['date'],
                    'start_time' => $slotStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'duration_hours' => $slot['duration'],
                ]);
            }

            // Save attendees if provided
            if ($request->has('attendees_passports') && is_array($request->attendees_passports)) {
                foreach ($request->attendees_passports as $passport) {
                    $trimmedPassport = trim($passport);
                    if (!empty($trimmedPassport)) {
                        // Additional validation check (should already be validated above, but safety check)
                        if (!$this->validationService->validateStudentIdFormat($trimmedPassport)) {
                            return response()->json([
                                'status' => 'F', 
                                'message' => 'Invalid attendee passport format. Must be in format YYWMR##### (e.g., 25WMR00001).',
                                'timestamp' => now()->format('Y-m-d H:i:s'), 
                            ], 422);
                        }
                        $booking->attendees()->create([
                            'student_passport' => $trimmedPassport,
                        ]);
                    }
                }
            }

            // Send notification to user
            if ($booking->status === 'approved') {
                $this->notificationService->sendBookingNotification($booking, 'approved', 'Your booking has been created and approved!');
            } else {
                $this->notificationService->sendBookingNotification($booking, 'pending', 'Your booking has been submitted and is pending approval.');
            }

            return response()->json([
                'status' => 'S', 
                'message' => 'Booking created successfully',
                'data' => $booking->load(['user', 'facility', 'attendees', 'slots']),
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 201);
        });
        } catch (\Illuminate\Validation\ValidationException $e) {
           
            
            return response()->json([
                'status' => 'F', 
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Booking creation error: ' . $e->getMessage());
            return response()->json([
                'status' => 'E', 
                'message' => 'Failed to create booking: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $booking = Booking::with(['user', 'facility', 'attendees', 'slots'])->findOrFail($id);
            
            $user = auth()->user();
            if (!$user->isAdmin() && $booking->user_id !== $user->id) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Unauthorized. You can only view your own bookings.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 403);
            }
            
            return response()->json([
                'status' => 'S', 
                'data' => $booking,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'F', 
                'message' => 'Booking not found',
                'error' => 'No query results for model [App\Models\Booking] ' . $id,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching booking: ' . $e->getMessage());
            return response()->json([
                'status' => 'E', 
                'message' => 'Error loading booking details',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'), 
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
            'status' => 'F', 
            'message' => 'Delete functionality is disabled. Please use reject or cancel instead.',
            'timestamp' => now()->format('Y-m-d H:i:s'), 
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
                'status' => 'F', 
                'message' => 'Only pending bookings can be approved',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
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
                'status' => 'F', 
                'message' => 'Cannot approve: ' . $capacityCheck['message'],
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 409);
        }

        $booking->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Send notification to user
        $this->notificationService->sendBookingNotification($booking, 'approved', 'Your booking has been approved!');

        return response()->json([
            'status' => 'S', 
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['user', 'facility', 'approver', 'attendees', 'slots']),
            'timestamp' => now()->format('Y-m-d H:i:s'), 
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
                'status' => 'F', 
                'message' => 'Only pending bookings can be rejected',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        // Send notification to user
        $this->notificationService->sendBookingNotification($booking, 'rejected', 'Your booking has been rejected. Reason: ' . $request->reason);

        return response()->json([
            'status' => 'S', 
            'message' => 'Booking rejected successfully',
            'data' => $booking->load(['user', 'facility', 'attendees', 'slots']),
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        
        // Security: Authorization check - Users can only cancel their own bookings
        // Admins and staff can cancel any booking
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isStaff() && $booking->user_id !== $user->id) {
            return response()->json([
                'status' => 'F', 
                'message' => 'Unauthorized. You can only cancel your own bookings.',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 403);
        }
        
        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason ?? null,
        ]);

        // Send notification to user (only if cancelled by admin, not by user themselves)
        if ($user->isAdmin()) {
            $this->notificationService->sendBookingNotification($booking, 'cancelled', 'Your booking has been cancelled' . ($request->reason ? '. Reason: ' . $request->reason : ''));
        }

        return response()->json([
            'status' => 'S', 
            'data' => $booking,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

    /**
     * Complete a booking (Admin/Staff only)
     * 当预订完成时，Observer 会自动处理积分奖励
     */
    public function complete(string $id)
    {
        $user = auth()->user();

        // 只有管理员或员工可以完成预订
        if (!$user->isAdmin() && !$user->isStaff()) {
            return response()->json([
                'status' => 'F', 
                'message' => 'Only administrators and staff can complete bookings',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 403);
        }

        $booking = Booking::findOrFail($id);

        // 只有已批准的预订可以完成
        if ($booking->status !== 'approved') {
            return response()->json([
                'status' => 'F', 
                'message' => 'Only approved bookings can be completed',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

        // 更新状态为 completed
        // Observer 会自动检测状态变化并奖励积分
        $booking->update([
            'status' => 'completed',
        ]);

        // Send notification to user
        $this->notificationService->sendBookingNotification($booking, 'completed', 'Your booking has been completed! Points have been awarded to your account.');

        return response()->json([
            'status' => 'S', 
            'message' => 'Booking completed successfully',
            'data' => $booking->load(['user', 'facility', 'attendees']),
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

    public function myBookings(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = auth()->user()->bookings()
            ->with(['user', 'facility', 'attendees', 'slots'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->facility_id, fn($q) => $q->where('facility_id', $request->facility_id))
            ->when($request->search, function($q) use ($request) {
                $search = $request->search;
                $q->where(function($query) use ($search) {
                    $query->where('id', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%");
                });
            });
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if ($sortBy === 'date') {
            $query->orderBy('booking_date', $sortOrder);
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $bookings = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'S', 
            'data' => $bookings,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
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

        //Service Consumption Get facility info via HTTP from Facility Management Module
        $baseUrl = config('app.url', 'http://localhost:8000');
        $apiUrl = rtrim($baseUrl, '/') . '/api/facilities/service/get-info';
        
        $facilityResponse = Http::timeout(10)->post($apiUrl, [
            'facility_id' => $facilityId,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
        
        if (!$facilityResponse->successful()) {
            Log::warning('Failed to get facility from Facility Management Module', [
                'status' => $facilityResponse->status(),
            ]);
            // Fallback to direct query
            $facility = Facility::findOrFail($facilityId);
        } else {
            $facilityData = $facilityResponse->json();
            if ($facilityData['status'] === 'S' && isset($facilityData['data']['facility'])) {
                $facility = Facility::findOrFail($facilityId);
            } else {
                $facility = Facility::findOrFail($facilityId);
            }
        }

        // Check if user is student and facility type is not allowed
        // Staff can check availability for all facility types
        $user = auth()->user();
        if ($user && $user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
            return response()->json([
                'status' => 'F', 
                'is_available' => false,
                'message' => 'Students can only check availability for sports or library facilities.',
                'reason' => 'facility_type_restricted',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 403);
        }
        
        // Check if facility is available
        if ($facility->status !== 'available') {
            return response()->json([
                'status' => 'F', 
                'is_available' => false,
                'message' => 'Facility is not available',
                'reason' => $facility->status,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
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
                    'status' => 'F', 
                    'is_available' => false,
                    'message' => "This facility is not available on {$bookingDate->format('l, F j, Y')}. Available days: {$availableDaysStr}",
                    'reason' => 'day_not_available',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
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
                    'status' => 'F', 
                    'is_available' => false,
                    'message' => "Expected attendees ({$expectedAttendees}) exceed maximum allowed ({$facility->max_attendees}) for this facility",
                    'reason' => 'max_attendees_exceeded',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ]);
            }
        }
        
        // Find all pending and approved bookings that overlap with the requested time slot
        // Include pending bookings in capacity count
        $overlappingBookings = Booking::where('facility_id', $facilityId)
            ->whereDate('booking_date', $request->date)
            ->whereIn('status', ['pending', 'approved']) 
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })
            ->get();

        // Check if facility type requires full capacity occupation
        $isFullCapacityType = in_array($facility->type, ['classroom', 'auditorium', 'laboratory']);
        
        // Calculate total expected attendees for overlapping bookings
        // If facility has enable_multi_attendees OR is classroom/auditorium/laboratory, each booking occupies the full capacity
        $totalAttendees = $overlappingBookings->sum(function($booking) use ($facility, $isFullCapacityType) {
            // If this facility has enable_multi_attendees OR is full capacity type, each booking occupies full capacity
            if ($facility->enable_multi_attendees || $isFullCapacityType) {
                return $facility->capacity;
            }
            // Otherwise, use expected_attendees
            return $booking->expected_attendees ?? 1;
        });

        // For the new booking, if facility has enable_multi_attendees OR is full capacity type, it occupies full capacity
        $newBookingAttendees = ($facility->enable_multi_attendees || $isFullCapacityType)
            ? $facility->capacity 
            : $expectedAttendees;

        // Check if adding this booking would exceed capacity
        // If multi_attendees is enabled OR is full capacity type, only one booking per time slot is allowed
        $requiresFullCapacity = $facility->enable_multi_attendees || $isFullCapacityType;
        if ($requiresFullCapacity) {
            $isAvailable = $overlappingBookings->count() === 0;
            $totalAfterBooking = $isAvailable ? $facility->capacity : $facility->capacity;
        } else {
            $totalAfterBooking = $totalAttendees + $newBookingAttendees;
            $isAvailable = $totalAfterBooking <= $facility->capacity;
        }
        $availableCapacity = max(0, $facility->capacity - $totalAttendees);

        return response()->json([
            'status' => $isAvailable ? 'S' : 'F', 
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
            'timestamp' => now()->format('Y-m-d H:i:s'), 
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
            'status' => 'S', 
            'message' => 'Pending bookings retrieved successfully',
            'data' => [
                'bookings' => $bookings,
                'count' => $count,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

    /**
     * Web Service API: Get booking information
     * This endpoint is designed for inter-module communication
     * Used by other modules (e.g., Analytics Module, Reporting Module) to query booking information
     * 
     * IFA Standard Compliance:
     * - Request must include timestamp or requestID (mandatory)
     * - Response includes status and timestamp (mandatory)
     */
    public function getBookingInfo(Request $request)
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

        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::with(['user', 'facility', 'attendees', 'slots', 'approver'])
            ->findOrFail($request->booking_id);

        // IFA Standard Response Format
        return response()->json([
            'status' => 'S', // S: Success, F: Fail, E: Error (IFA Standard)
            'message' => 'Booking information retrieved successfully',
            'data' => [
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
                    'approved_by' => $booking->approved_by,
                    'approved_at' => $booking->approved_at ? $booking->approved_at->format('Y-m-d H:i:s') : null,
                    'cancelled_at' => $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
                ],
                'attendees_count' => $booking->attendees->count(),
                'slots_count' => $booking->slots->count(),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
        ]);
    }

}
