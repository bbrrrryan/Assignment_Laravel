<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = Facility::when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(15);
        
        // Get booking date from request if provided
        $bookingDate = $request->get('booking_date');
        
        // Add approved bookings count and total expected attendees for each facility
        $facilities->getCollection()->transform(function ($facility) use ($bookingDate) {
            $approvedBookingsQuery = $facility->bookings()
                ->where('status', 'approved');
            
            // If booking date is provided, filter by date
            if ($bookingDate) {
                $approvedBookingsQuery->whereDate('booking_date', $bookingDate);
            }
            
            $approvedBookings = $approvedBookingsQuery->get();
            
            $facility->approved_bookings_count = $approvedBookings->count();
            // Sum expected_attendees, treating null as 0
            $facility->total_approved_attendees = $approvedBookings->sum(function($booking) {
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

    public function show(string $id)
    {
        return response()->json(['data' => Facility::with('bookings')->findOrFail($id)]);
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
        $facility = Facility::findOrFail($id);
        
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ]);

        $date = $request->date;
        $startTime = $request->start_time;
        $endTime = $request->end_time;

        // Get all bookings for this facility on the given date
        $bookings = $facility->bookings()
            ->whereDate('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->get();

        // If specific time range provided, check conflicts
        if ($startTime && $endTime) {
            $conflicts = $bookings->filter(function($booking) use ($startTime, $endTime) {
                $bookingStart = $booking->start_time->format('H:i');
                $bookingEnd = $booking->end_time->format('H:i');
                
                // Check if time ranges overlap
                return ($startTime < $bookingEnd && $endTime > $bookingStart);
            });

            $isAvailable = $conflicts->isEmpty();
            
            return response()->json([
                'message' => 'Availability checked',
                'data' => [
                    'facility_id' => $facility->id,
                    'date' => $date,
                    'time_range' => [
                        'start' => $startTime,
                        'end' => $endTime,
                    ],
                    'is_available' => $isAvailable,
                    'conflicting_bookings' => $conflicts->count(),
                ],
            ]);
        }

        // Return all bookings for the day
        return response()->json([
            'message' => 'Availability retrieved',
            'data' => [
                'facility_id' => $facility->id,
                'date' => $date,
                'bookings' => $bookings->map(function($booking) {
                    return [
                        'id' => $booking->id,
                        'start_time' => $booking->start_time->format('H:i'),
                        'end_time' => $booking->end_time->format('H:i'),
                        'status' => $booking->status,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get facility utilization statistics
     */
    public function utilization(string $id, Request $request)
    {
        $facility = Facility::findOrFail($id);
        
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
