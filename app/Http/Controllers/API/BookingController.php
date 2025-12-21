<?php
/**
 * Author: Low Kim Hong
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Facility;
use App\Builders\BookingBuilder;
use App\Services\BookingValidationService;
use App\Services\BookingCapacityService;
use App\Services\BookingNotificationService;
use App\Services\FacilityWebService;
use App\Services\UserWebService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected $validationService;
    protected $capacityService;
    protected $notificationService;
    protected $facilityWebService;
    protected $userWebService;

    public function __construct(
        BookingValidationService $validationService,
        BookingCapacityService $capacityService,
        BookingNotificationService $notificationService,
        FacilityWebService $facilityWebService,
        UserWebService $userWebService
    ) {
        $this->validationService = $validationService;
        $this->capacityService = $capacityService;
        $this->notificationService = $notificationService;
        $this->facilityWebService = $facilityWebService;
        $this->userWebService = $userWebService;
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

  
    public function store(Request $request)
    {
        try {
        
            
            $facilityId = $request->input('facility_id');
            if (!$facilityId) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Facility ID is required',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 422);
            }
            
            try {
                $facility = $this->facilityWebService->getFacilityInfo($facilityId);
            } catch (\Exception $e) {
                Log::error('Failed to get facility from Web Service', [
                    'facility_id' => $facilityId,
                    'error' => $e->getMessage(),
                ]);
                
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Unable to retrieve facility information. The facility service is currently unavailable. Please try again later.',
                    'error_details' => $e->getMessage(),
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 503);
            }
            
            $timeSlots = $request->input('time_slots', []);
            $useTimeSlots = !empty($timeSlots) && is_array($timeSlots);
            
            if ($useTimeSlots) {
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
                
                if ($error = $this->validationService->validateAvailableDay($bookingDate, $facility)) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }
                
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
                    'attendees_passports' => [
                        'nullable',
                        'array',
                        function ($attribute, $value, $fail) {
                            if (is_array($value) && !empty($value)) {
                                $user = auth()->user();
                                $userPersonalId = $user->personal_id ?? null;
                                
                                $trimmedPassports = array_filter(array_map('trim', $value), function($passport) {
                                    return !empty($passport);
                                });
                                
                                $uniquePassports = array_unique($trimmedPassports);
                                if (count($trimmedPassports) !== count($uniquePassports)) {
                                    $fail('Duplicate student passport numbers are not allowed. Each attendee must have a unique passport number.');
                                }
                                
                                if (!empty($userPersonalId)) {
                                    foreach ($trimmedPassports as $passport) {
                                        if (strcasecmp($passport, $userPersonalId) === 0) {
                                            $fail('You cannot add your own student ID as an attendee passport. The booking owner is automatically included.');
                                        }
                                    }
                                }
                            }
                        },
                    ],
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
                $validationRules = $this->validationService->getValidationRules($facility);
                
                $existingAttendeesRule = $validationRules['attendees_passports'] ?? ['nullable', 'array'];
                if (!is_array($existingAttendeesRule)) {
                    $existingAttendeesRule = [$existingAttendeesRule];
                }
                
                $validationRules['attendees_passports'] = array_merge(
                    $existingAttendeesRule,
                    [
                        function ($attribute, $value, $fail) {
                            if (is_array($value) && !empty($value)) {
                                $user = auth()->user();
                                $userPersonalId = $user->personal_id ?? null;
                                
                                $trimmedPassports = array_filter(array_map('trim', $value), function($passport) {
                                    return !empty($passport);
                                });
                                
                                $uniquePassports = array_unique($trimmedPassports);
                                if (count($trimmedPassports) !== count($uniquePassports)) {
                                    $fail('Duplicate student passport numbers are not allowed. Each attendee must have a unique passport number.');
                                }
                                
                                if (!empty($userPersonalId)) {
                                    foreach ($trimmedPassports as $passport) {
                                        if (strcasecmp($passport, $userPersonalId) === 0) {
                                            $fail('You cannot add your own student ID as an attendee passport. The booking owner is automatically included.');
                                        }
                                    }
                                }
                            }
                        },
                    ]
                );
                
                $validated = $request->validate($validationRules);

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

                if ($error = $this->validationService->validateTimeRange($validated['start_time'], $validated['end_time'])) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }

                if ($error = $this->validationService->validateAvailableDay($validated['booking_date'], $facility)) {
                    return response()->json([
                        'status' => 'F', 
                        'message' => $error,
                        'timestamp' => now()->format('Y-m-d H:i:s'), 
                    ], 422);
                }

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
                
                $parsedSlots = [[
                    'date' => $validated['booking_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'duration' => \Carbon\Carbon::parse($validated['start_time'])->diffInHours(\Carbon\Carbon::parse($validated['end_time'])),
                ]];
            }

            $validated['expected_attendees'] = $this->validationService->normalizeExpectedAttendees(
                $validated['expected_attendees'] ?? null,
                $facility
            );

            if ($error = $this->validationService->validateFacilityStatus($facility)) {
                return response()->json([
                    'status' => 'F', 
                    'message' => $error,
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 400);
            }

        $durationHours = $validated['duration_hours'] ?? 0;
        
        if ($durationHours <= 0) {
            return response()->json([
                'status' => 'F', 
                'message' => 'Invalid booking duration',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

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

        $expectedAttendees = $validated['expected_attendees'];
        
        if ($error = $this->validationService->validateCapacity($expectedAttendees, $facility)) {
            return response()->json([
                'status' => 'F', 
                'message' => $error,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

        if ($request->has('attendees_passports') && is_array($request->attendees_passports)) {
            $user = auth()->user();
            $userPersonalId = $user->personal_id;
            
            $trimmedPassports = [];
            foreach ($request->attendees_passports as $index => $passport) {
                $trimmed = trim($passport);
                if (!empty($trimmed)) {
                    $trimmedPassports[$index] = $trimmed;
                }
            }
            
            $uniquePassports = array_unique($trimmedPassports);
            if (count($trimmedPassports) !== count($uniquePassports)) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Duplicate student passport numbers are not allowed. Each attendee must have a unique passport number.',
                    'errors' => [
                        'attendees_passports' => ['Duplicate student passport numbers are not allowed. Each attendee must have a unique passport number.'],
                    ],
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 422);
            }
            
            $passportErrors = [];
            if (!empty($userPersonalId)) {
                foreach ($trimmedPassports as $index => $passport) {
                    if (strcasecmp($passport, $userPersonalId) === 0) {
                        $passportErrors["attendees_passports.{$index}"] = ['You cannot add your own student ID as an attendee passport. The booking owner is automatically included.'];
                    }
                }
            }
            
            
            foreach ($trimmedPassports as $index => $passport) {
                if (isset($passportErrors["attendees_passports.{$index}"])) {
                    continue;
                }
                if (!$this->validationService->validateStudentIdFormat($passport)) {
                    $passportErrors["attendees_passports.{$index}"] = ['Invalid format. Must be YYWMR##### (e.g., 25WMR00001).'];
                    continue;
                }
                try {
                    $result = $this->userWebService->checkByPersonalId($passport);
                    
                    if (!$result['exists']) {
                        $passportErrors["attendees_passports.{$index}"] = ["Passport ID '{$passport}' does not exist or is not active. Please check the ID and try again."];
                        continue;
                    }
                    
                    if ($result['user'] && isset($result['user']['role']) && $result['user']['role'] !== 'student') {
                        $passportErrors["attendees_passports.{$index}"] = ["User with passport ID '{$passport}' is not a student. Only students can be added as attendees."];
                        continue;
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Failed to verify attendee personal ID via Web Service', [
                        'personal_id' => $passport,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $passportErrors["attendees_passports.{$index}"] = ['Unable to verify this passport. The user service is currently unavailable. Please try again later.'];
                }
            }
            
            if (!empty($passportErrors)) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Some attendee passport IDs are invalid or do not exist.',
                    'errors' => $passportErrors,
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 422);
            }
        }

        return DB::transaction(function () use ($parsedSlots, $validated, $facility, $expectedAttendees, $request) {
            $facility = Facility::lockForUpdate()->findOrFail($validated['facility_id']);
            
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

            $user = auth()->user();
            
            if ($user->isAdmin()) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Admins cannot create bookings through this endpoint. Please use the admin panel.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 403);
            }
            
            if ($user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
                return response()->json([
                    'status' => 'F', 
                    'message' => 'Students can only book sports or library facilities.',
                    'timestamp' => now()->format('Y-m-d H:i:s'), 
                ], 403);
            }
            
            $bookingStatus = 'pending';

            $startTime = $validated['start_time'];
            $endTime = $validated['end_time'];
            
            if (strlen($startTime) > 5 && strpos($startTime, ' ') !== false) {
                $startTime = \Carbon\Carbon::parse($startTime)->format('H:i');
            }
            
            if (strlen($endTime) > 5 && strpos($endTime, ' ') !== false) {
                $endTime = \Carbon\Carbon::parse($endTime)->format('H:i');
            }

            $builder = BookingBuilder::create()
                ->forUser(auth()->id())
                ->forFacility($validated['facility_id'])
                ->onDate($validated['booking_date'])
                ->fromTime($startTime)
                ->toTime($endTime)
                ->withPurpose($validated['purpose'])
                ->withStatus($bookingStatus)
                ->withExpectedAttendees($expectedAttendees)
                ->withDuration($validated['duration_hours']); 


            $builder->addSlots($parsedSlots);


            $booking = $builder->build();


            try {
                $user->activityLogs()->create([
                    'action' => 'create_booking',
                    'description' => "Created booking for facility: {$facility->name} on {$validated['booking_date']}",
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create booking activity log: ' . $e->getMessage());
            }

            if ($request->has('attendees_passports') && is_array($request->attendees_passports)) {
                $trimmedPassports = [];
                foreach ($request->attendees_passports as $index => $passport) {
                    $trimmed = trim($passport);
                    if (!empty($trimmed)) {
                        $trimmedPassports[] = $trimmed;
                    }
                }
                
                foreach ($trimmedPassports as $passport) {
                    $booking->attendees()->create([
                        'student_passport' => $passport,
                    ]);
                }
            }

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

        $this->notificationService->sendBookingNotification($booking, 'approved', 'Your booking has been approved!');

        return response()->json([
            'status' => 'S', 
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['user', 'facility', 'approver', 'attendees', 'slots']),
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

 
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
        
        $user = auth()->user();
        if (!$user->isAdmin() && $booking->user_id !== $user->id) {
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

        if ($user->isAdmin()) {
            $this->notificationService->sendBookingNotification($booking, 'cancelled', 'Your booking has been cancelled' . ($request->reason ? '. Reason: ' . $request->reason : ''));
        }

        return response()->json([
            'status' => 'S', 
            'data' => $booking,
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

 
    public function complete(string $id)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'F', 
                'message' => 'Only administrators and staff can complete bookings',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 403);
        }

        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'approved') {
            return response()->json([
                'status' => 'F', 
                'message' => 'Only approved bookings can be completed',
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 400);
        }

        $booking->update([
            'status' => 'completed',
        ]);

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



    public function checkAvailability(string $facilityId, Request $request)
    {
        $request->validate([
            'date' => 'required|date|after:today', 
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
            'expected_attendees' => 'nullable|integer|min:1',
        ]);

        try {
            $facility = $this->facilityWebService->getFacilityInfo($facilityId);
        } catch (\Exception $e) {
            Log::error('Failed to get facility from Web Service in availability check', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => 'F', 
                'message' => 'Unable to retrieve facility information. The facility service is currently unavailable. Please try again later.',
                'error_details' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ], 503);
        }

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
        
        if ($facility->status !== 'available') {
            return response()->json([
                'status' => 'F', 
                'is_available' => false,
                'message' => 'Facility is not available',
                'reason' => $facility->status,
                'timestamp' => now()->format('Y-m-d H:i:s'), 
            ]);
        }

        $bookingDate = \Carbon\Carbon::parse($request->date);
        $dayOfWeek = strtolower($bookingDate->format('l')); 
        
        if ($facility->available_day && is_array($facility->available_day) && !empty($facility->available_day)) {
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

        $expectedAttendees = $request->input('expected_attendees');
        if (!$facility->enable_multi_attendees) {
            $expectedAttendees = 1;
        } else {
            $expectedAttendees = $expectedAttendees ?? 1;
            
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

        $isFullCapacityType = in_array($facility->type, ['classroom', 'auditorium', 'laboratory']);
        
        $totalAttendees = $overlappingBookings->sum(function($booking) use ($facility, $isFullCapacityType) {
            if ($facility->enable_multi_attendees || $isFullCapacityType) {
                return $facility->capacity;
            }
            return $booking->expected_attendees ?? 1;
        });

        $newBookingAttendees = ($facility->enable_multi_attendees || $isFullCapacityType)
            ? $facility->capacity 
            : $expectedAttendees;

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

    
    public function getBookingInfo(Request $request)
    {
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

        $formattedSlots = [];
        if ($booking->slots && $booking->slots->count() > 0) {
            $formattedSlots = $booking->slots->map(function($slot) {
                $slotDate = $slot->slot_date->format('Y-m-d');
                $startDateTime = $slotDate . ' ' . $slot->start_time;
                $endDateTime = $slotDate . ' ' . $slot->end_time;
                
                return [
                    'slot_date' => $slotDate,
                    'start_time' => $startDateTime,
                    'end_time' => $endDateTime,
                    'duration_hours' => $slot->duration_hours,
                ];
            })->toArray();
        }

        return response()->json([
            'status' => 'S',    
            'message' => 'Booking information retrieved successfully',
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'facility_name' => $booking->facility->name ?? null,
                    'facility_code' => $booking->facility->code ?? null,
                    'booking_date' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                    'duration_hours' => $booking->duration_hours,
                    'purpose' => $booking->purpose,
                    'user_name' => $booking->user->name ?? null,
                    'slots' => $formattedSlots,
                ],
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

}

