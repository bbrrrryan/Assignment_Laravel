<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = Facility::where('is_deleted', false)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(15);
        
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
        
        return response()->json(['data' => $facilities]);
    }

    public function store(Request $request)
    {
        $facility = Facility::create($request->validate([
            'name' => 'required',
            'code' => 'required|unique:facilities',
            'type' => 'required',
            'location' => 'required',
            'capacity' => 'required|integer',
        ]));
        return response()->json(['data' => $facility], 201);
    }

    public function show(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->with('bookings')->findOrFail($id);
        
        // Check if user is student and facility type is not allowed
        $user = $request->user();
        if ($user && $user->isStudent() && !in_array($facility->type, ['sports', 'library'])) {
            return response()->json([
                'message' => 'You are not allowed to view this facility.'
            ], 403);
        }
        
        return response()->json(['data' => $facility]);
    }

    public function update(Request $request, string $id)
    {
        $facility = Facility::findOrFail($id);
        $facility->update($request->all());
        return response()->json(['data' => $facility]);
    }

    public function destroy(string $id)
    {
        Facility::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Check facility availability for a date range
     */
    public function availability(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
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
                $totalAfterBooking = $isAvailable ? $facility->capacity : $facility->capacity;
            } else {
                $totalAfterBooking = $totalAttendees + $newBookingAttendees;
                $isAvailable = $totalAfterBooking <= $facility->capacity;
                $availableCapacity = max(0, $facility->capacity - $totalAttendees);
            }
            
            return response()->json([
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
            ]);
        }

        // Return all bookings for the day with capacity information
        return response()->json([
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
        ]);
    }

    /**
     * Get facility utilization statistics
     */
    public function utilization(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        // Get all bookings in the date range
        $bookings = $facility->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->get();

        $totalBookings = $bookings->count();
        $totalHours = $bookings->sum('duration_hours');
        $uniqueUsers = $bookings->pluck('user_id')->unique()->count();

        // Calculate utilization percentage (assuming facility is available 8 hours/day)
        $daysInRange = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $maxPossibleHours = $daysInRange * 8; // Assuming 8 hours per day
        $utilizationPercentage = $maxPossibleHours > 0 ? ($totalHours / $maxPossibleHours) * 100 : 0;

        // Group by status
        $statusBreakdown = $facility->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'message' => 'Utilization statistics retrieved',
            'data' => [
                'facility' => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'capacity' => $facility->capacity,
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'statistics' => [
                    'total_bookings' => $totalBookings,
                    'total_hours' => round($totalHours, 2),
                    'unique_users' => $uniqueUsers,
                    'utilization_percentage' => round($utilizationPercentage, 2),
                    'status_breakdown' => $statusBreakdown,
                ],
            ],
        ]);
    }
}
