<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
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
                'booking_date' => 'required|date|after_or_equal:today',
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

        // Check if facility is available
        if ($facility->status !== 'available') {
            return response()->json([
                'message' => 'Facility is not available for booking',
            ], 400);
        }

        // Check for conflicts
        $conflicts = Booking::where('facility_id', $validated['facility_id'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                      });
            })
            ->exists();

        if ($conflicts) {
            return response()->json([
                'message' => 'Time slot is already booked. Please choose a different time.',
            ], 409);
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

        // Check capacity - use request input to safely access nullable field
        $expectedAttendees = $request->input('expected_attendees');
        if ($expectedAttendees && $expectedAttendees > $facility->capacity) {
            return response()->json([
                'message' => 'Expected attendees exceed facility capacity',
            ], 400);
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

        // Determine booking status based on user role
        // Students always create pending bookings that require admin approval
        // Admin can create approved bookings directly (if facility doesn't require approval)
        $user = auth()->user();
        
        if ($user->isStudent()) {
            // Students always create pending bookings that require admin approval
            $bookingStatus = 'pending';
        } elseif ($user->isAdmin()) {
            // Admin can create approved bookings if facility doesn't require approval
            $bookingStatus = $facility->requires_approval ? 'pending' : 'approved';
        } else {
            // Default to pending for other roles (staff, etc.)
            $bookingStatus = 'pending';
        }

        $booking = Booking::create([
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
        ]);

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
        return response()->json(['data' => Booking::with(['user', 'facility', 'statusHistory'])->findOrFail($id)]);
    }

    public function update(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update($request->all());
        return response()->json(['data' => $booking]);
    }

    public function destroy(string $id)
    {
        Booking::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
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

        // Check for conflicts before approving
        $conflicts = Booking::where('facility_id', $booking->facility_id)
            ->where('id', '!=', $booking->id)
            ->whereDate('booking_date', $booking->booking_date)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($booking) {
                $query->whereBetween('start_time', [$booking->start_time, $booking->end_time])
                      ->orWhereBetween('end_time', [$booking->start_time, $booking->end_time])
                      ->orWhere(function($q) use ($booking) {
                          $q->where('start_time', '<=', $booking->start_time)
                            ->where('end_time', '>=', $booking->end_time);
                      });
            })
            ->exists();

        if ($conflicts) {
            return response()->json([
                'message' => 'Cannot approve: Time slot conflicts with existing booking',
            ], 409);
        }

        $booking->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Create status history
        $booking->statusHistory()->create([
            'status' => 'approved',
            'changed_by' => auth()->id(),
            'notes' => 'Booking approved by admin',
        ]);

        return response()->json([
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['user', 'facility', 'approver']),
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);
        return response()->json(['data' => $booking]);
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);
        return response()->json(['data' => $booking]);
    }

    public function myBookings()
    {
        return response()->json(['data' => auth()->user()->bookings]);
    }

    /**
     * Check facility availability for booking
     */
    public function checkAvailability(string $facilityId, Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
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

        // Check for conflicts
        $conflicts = Booking::where('facility_id', $facilityId)
            ->whereDate('booking_date', $request->date)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })
            ->get();

        $isAvailable = $conflicts->isEmpty();

        return response()->json([
            'is_available' => $isAvailable,
            'message' => $isAvailable ? 'Time slot is available' : 'Time slot is already booked',
            'data' => [
                'facility_id' => $facilityId,
                'date' => $request->date,
                'time_range' => [
                    'start' => $request->start_time,
                    'end' => $request->end_time,
                ],
                'conflicting_bookings' => $conflicts->map(function($booking) {
                    return [
                        'id' => $booking->id,
                        'start_time' => $booking->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $booking->end_time->format('Y-m-d H:i:s'),
                        'status' => $booking->status,
                    ];
                }),
            ],
        ]);
    }
}
