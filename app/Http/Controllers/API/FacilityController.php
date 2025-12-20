<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $perPage = $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100
        
        $search = $request->input('search');

        $facilities = Facility::where('is_deleted', false)
            // Text search by name, code, or location (if provided)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            // Type and status filters
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            // Only students are restricted to sports and library facilities
            // Staff can see all facilities
            ->when($user && $user->isStudent(), fn($q) => $q->whereIn('type', ['sports', 'library']))
            ->paginate($perPage);
        
        // Get booking date from request if provided
        $bookingDate = $request->get('booking_date');
        
        // Add bookings count and total expected attendees for each facility
        // Include both pending and approved bookings in the count
        $facilities->getCollection()->transform(function ($facility) use ($bookingDate) {
            $bookingsQuery = $facility->bookings()
                ->whereIn('status', ['pending', 'approved']); // Include pending and approved
            
            // If booking date is provided, filter by date
            if ($bookingDate) {
                $bookingsQuery->whereDate('booking_date', $bookingDate);
            }
            
            $bookings = $bookingsQuery->get();
            
            $facility->approved_bookings_count = $bookings->where('status', 'approved')->count();
            $facility->pending_bookings_count = $bookings->where('status', 'pending')->count();
            // Sum expected_attendees for both pending and approved bookings
            $facility->total_approved_attendees = $bookings->sum(function($booking) {
                return $booking->expected_attendees ?? 0;
            });
            $facility->is_at_capacity = ($facility->total_approved_attendees >= $facility->capacity);
            
            return $facility;
        });
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $facilities,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function show(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->with('bookings')->findOrFail($id);
        
        // Check if user is student and facility type is not allowed
        // Staff can view all facilities
        $user = $request->user();
        if ($user && $user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'You are not allowed to view this facility.',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 403);
        }
        
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $facility,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    /**
     * Check facility availability for a date range
     */
    public function availability(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        // Check if user is student and facility type is not allowed
        // Staff can check availability for all facilities
        $user = $request->user();
        if ($user && $user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
            return response()->json([
                'status' => 'F', // IFA Standard: F (Fail)
                'message' => 'You are not allowed to check availability for this facility. Students can only book sports or library facilities.',
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ], 403);
        }
        
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'expected_attendees' => 'nullable|integer|min:1',
        ]);

        $date = $request->date;
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $expectedAttendees = $request->input('expected_attendees', 1); // Default to 1

        // Get all pending and approved bookings for this facility on the given date
        // Include pending bookings in capacity count
        // Load slots relationship to support separate time slots
        $bookings = $facility->bookings()
            ->with('slots')
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved']) // Include pending and approved
            ->get();

        // If specific time range provided, check capacity
        if ($startTime && $endTime) {
            // Find overlapping bookings (pending and approved)
            $overlappingBookings = $bookings->filter(function($booking) use ($startTime, $endTime) {
                $bookingStart = $booking->start_time->format('H:i');
                $bookingEnd = $booking->end_time->format('H:i');
                
                // Check if time ranges overlap
                return ($startTime < $bookingEnd && $endTime > $bookingStart);
            });

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
                $availableCapacity = $isAvailable ? $facility->capacity : 0;
                $totalAfterBooking = $facility->capacity; // Always equals capacity when multi_attendees is enabled
            } else {
                $totalAfterBooking = $totalAttendees + $newBookingAttendees;
                $isAvailable = $totalAfterBooking <= $facility->capacity;
                $availableCapacity = max(0, $facility->capacity - $totalAttendees);
            }
            
            return response()->json([
                'status' => 'S', // IFA Standard
                'message' => 'Availability checked',
                'data' => [
                    'facility_id' => $facility->id,
                    'facility_capacity' => $facility->capacity,
                    'date' => $date,
                    'time_range' => [
                        'start' => $startTime,
                        'end' => $endTime,
                    ],
                    'expected_attendees' => $expectedAttendees,
                    'current_booked_attendees' => $totalAttendees,
                    'available_capacity' => $availableCapacity,
                    'total_after_booking' => $totalAfterBooking,
                    'is_available' => $isAvailable,
                    'overlapping_bookings_count' => $overlappingBookings->count(),
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ]);
        }

        // Return all bookings for the day with capacity information
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Availability retrieved',
            'data' => [
                'facility_id' => $facility->id,
                'facility_capacity' => $facility->capacity,
                'date' => $date,
                'bookings' => $bookings->map(function($booking) {
                    $bookingData = [
                        'id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'start_time' => $booking->start_time->format('H:i'),
                        'end_time' => $booking->end_time->format('H:i'),
                        'status' => $booking->status,
                        'expected_attendees' => $booking->expected_attendees ?? 1,
                    ];
                    
                    // Include slots if available (new format)
                    if ($booking->slots && $booking->slots->count() > 0) {
                        $bookingData['slots'] = $booking->slots->map(function($slot) {
                            // slot_date is a Carbon date object (cast as 'date')
                            // start_time and end_time are strings (time format: "HH:mm:ss")
                            return [
                                'id' => $slot->id,
                                'slot_date' => $slot->slot_date->format('Y-m-d'),
                                'start_time' => $slot->start_time, // Already a string like "08:00:00"
                                'end_time' => $slot->end_time,     // Already a string like "09:00:00"
                                'duration_hours' => $slot->duration_hours,
                            ];
                        });
                    }
                    
                    return $bookingData;
                }),
                'total_booked_attendees' => $bookings->sum(function($booking) {
                    return $booking->expected_attendees ?? 1;
                }),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function getFacilityInfo(Request $request)
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
            'facility_id' => 'required|exists:facilities,id',
        ]);

        $facility = Facility::where('is_deleted', false)
            ->with('bookings')
            ->findOrFail($request->facility_id);

        // IFA Standard Response Format
        return response()->json([
            'status' => 'S', // S: Success, F: Fail, E: Error (IFA Standard)
            'message' => 'Facility information retrieved successfully',
            'data' => [
                'facility' => $facility,
                'capacity' => $facility->capacity,
                'status' => $facility->status,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'), 
        ]);
    }

    public function checkAvailabilityService(Request $request)
    {
        // IFA Standard: Validate mandatory fields
        if (!$request->has('timestamp') && !$request->has('requestID')) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation error: timestamp or requestID is mandatory',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'expected_attendees' => 'nullable|integer|min:1',
        ]);

        $facility = Facility::where('is_deleted', false)->findOrFail($request->facility_id);
        
        // Use existing availability logic
        $availability = $this->availability($facility->id, $request);
        $data = json_decode($availability->getContent(), true);
        
        return response()->json([
            'status' => 'S',
            'message' => 'Availability checked successfully',
            'data' => $data['data'] ?? $data,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
