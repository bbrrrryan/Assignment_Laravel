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
        $perPage = min(max($perPage, 1), 100);
        
        $search = $request->input('search');

        $facilities = Facility::where('is_deleted', false)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($user && $user->isStudent(), fn($q) => $q->whereIn('type', ['sports', 'library']))
            ->paginate($perPage);
        
        $bookingDate = $request->get('booking_date');
        
        $facilities->getCollection()->transform(function ($facility) use ($bookingDate) {
            $bookingsQuery = $facility->bookings()
                ->whereIn('status', ['pending', 'approved']);
            
            if ($bookingDate) {
                $bookingsQuery->whereDate('booking_date', $bookingDate);
            }
            
            $bookings = $bookingsQuery->get();
            
            $facility->approved_bookings_count = $bookings->where('status', 'approved')->count();
            $facility->pending_bookings_count = $bookings->where('status', 'pending')->count();
            $facility->total_approved_attendees = $bookings->sum(function($booking) {
                return $booking->expected_attendees ?? 0;
            });
            $facility->is_at_capacity = ($facility->total_approved_attendees >= $facility->capacity);
            
            return $facility;
        });
        
        return response()->json([
            'status' => 'S',
            'data' => $facilities,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function show(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->with('bookings')->findOrFail($id);
        
        $user = $request->user();
        if ($user && $user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
            return response()->json([
                'status' => 'F',
                'message' => 'You are not allowed to view this facility.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 403);
        }
        
        return response()->json([
            'status' => 'S',
            'data' => $facility,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check facility availability for a date range
     */
    public function availability(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        $user = $request->user();
        if ($user && $user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
            return response()->json([
                'status' => 'F',
                'message' => 'You are not allowed to check availability for this facility. Students can only book sports or library facilities.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
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
        $expectedAttendees = $request->input('expected_attendees', 1);

        $bookings = $facility->bookings()
            ->with('slots')
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        if ($startTime && $endTime) {
            $overlappingBookings = $bookings->filter(function($booking) use ($startTime, $endTime) {
                $bookingStart = $booking->start_time->format('H:i');
                $bookingEnd = $booking->end_time->format('H:i');
                return ($startTime < $bookingEnd && $endTime > $bookingStart);
            });

            $totalAttendees = $overlappingBookings->sum(function($booking) use ($facility) {
                if ($facility->enable_multi_attendees) {
                    return $facility->capacity;
                }
                return $booking->expected_attendees ?? 1;
            });

            $newBookingAttendees = $facility->enable_multi_attendees 
                ? $facility->capacity 
                : $expectedAttendees;

            if ($facility->enable_multi_attendees) {
                $isAvailable = $overlappingBookings->count() === 0;
                $availableCapacity = $isAvailable ? $facility->capacity : 0;
                $totalAfterBooking = $facility->capacity;
            } else {
                $totalAfterBooking = $totalAttendees + $newBookingAttendees;
                $isAvailable = $totalAfterBooking <= $facility->capacity;
                $availableCapacity = max(0, $facility->capacity - $totalAttendees);
            }
            
            return response()->json([
                'status' => 'S',
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
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        return response()->json([
            'status' => 'S',
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
                    
                    if ($booking->slots && $booking->slots->count() > 0) {
                        $bookingData['slots'] = $booking->slots->map(function($slot) {
                            return [
                                'id' => $slot->id,
                                'slot_date' => $slot->slot_date->format('Y-m-d'),
                                'start_time' => $slot->start_time,
                                'end_time' => $slot->end_time,
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
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getFacilityInfo(Request $request)
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
            'facility_id' => 'required|exists:facilities,id',
        ]);

        $facility = Facility::where('is_deleted', false)
            ->with('bookings')
            ->findOrFail($request->facility_id);

        return response()->json([
            'status' => 'S',
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
