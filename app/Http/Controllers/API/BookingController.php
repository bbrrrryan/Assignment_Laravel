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
            
            // Validate time range: must be between 8:00 AM and 8:00 PM
            $startHour = $startTime->format('H:i');
            $endHour = $endTime->format('H:i');
            $minTime = '08:00';
            $maxTime = '20:00';
            
            if ($startHour < $minTime || $startHour > $maxTime) {
                return response()->json([
                    'message' => 'Start time must be between 8:00 AM and 8:00 PM',
                ], 422);
            }
            
            if ($endHour < $minTime || $endHour > $maxTime) {
                return response()->json([
                    'message' => 'End time must be between 8:00 AM and 8:00 PM',
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

            // Check permissions: Admin can modify any booking, users can only modify their own pending bookings
            if (!$user->isAdmin() && $booking->user_id !== $user->id) {
                return response()->json([
                    'message' => 'You do not have permission to modify this booking',
                ], 403);
            }

            // Users can only modify their own pending bookings
            if (!$user->isAdmin() && $booking->status !== 'pending') {
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

            // Validate time range if both times are provided
            if (isset($validated['start_time']) && isset($validated['end_time'])) {
                $startTime = \Carbon\Carbon::parse($validated['start_time']);
                $endTime = \Carbon\Carbon::parse($validated['end_time']);
                
                if ($endTime->lte($startTime)) {
                    return response()->json([
                        'message' => 'End time must be after start time',
                    ], 422);
                }
                
                // Validate time range: must be between 8:00 AM and 8:00 PM
                $startHour = $startTime->format('H:i');
                $endHour = $endTime->format('H:i');
                $minTime = '08:00';
                $maxTime = '20:00';
                
                if ($startHour < $minTime || $startHour > $maxTime) {
                    return response()->json([
                        'message' => 'Start time must be between 8:00 AM and 8:00 PM',
                    ], 422);
                }
                
                if ($endHour < $minTime || $endHour > $maxTime) {
                    return response()->json([
                        'message' => 'End time must be between 8:00 AM and 8:00 PM',
                    ], 422);
                }

                $validated['duration_hours'] = $startTime->diffInHours($endTime);
            }
            
            // Validate time range if only start_time is provided
            if (isset($validated['start_time']) && !isset($validated['end_time'])) {
                $startTime = \Carbon\Carbon::parse($validated['start_time']);
                $startHour = $startTime->format('H:i');
                $minTime = '08:00';
                $maxTime = '20:00';
                
                if ($startHour < $minTime || $startHour > $maxTime) {
                    return response()->json([
                        'message' => 'Start time must be between 8:00 AM and 8:00 PM',
                    ], 422);
                }
            }
            
            // Validate time range if only end_time is provided
            if (isset($validated['end_time']) && !isset($validated['start_time'])) {
                $endTime = \Carbon\Carbon::parse($validated['end_time']);
                $endHour = $endTime->format('H:i');
                $minTime = '08:00';
                $maxTime = '20:00';
                
                if ($endHour < $minTime || $endHour > $maxTime) {
                    return response()->json([
                        'message' => 'End time must be between 8:00 AM and 8:00 PM',
                    ], 422);
                }
            }

            // Get facility (use existing or new one)
            $facilityId = $validated['facility_id'] ?? $booking->facility_id;
            $facility = Facility::findOrFail($facilityId);

            // Check capacity if expected_attendees is provided
            if (isset($validated['expected_attendees']) && $validated['expected_attendees'] > $facility->capacity) {
                return response()->json([
                    'message' => 'Expected attendees exceed facility capacity',
                ], 400);
            }

            // Check for conflicts (exclude current booking and cancelled bookings)
            $bookingDate = $validated['booking_date'] ?? $booking->booking_date;
            $startTime = $validated['start_time'] ?? $booking->start_time;
            $endTime = $validated['end_time'] ?? $booking->end_time;

            $conflicts = Booking::where('facility_id', $facilityId)
                ->where('id', '!=', $booking->id)
                ->whereDate('booking_date', $bookingDate)
                ->where('status', '!=', 'cancelled')
                ->where(function($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime])
                          ->orWhereBetween('end_time', [$startTime, $endTime])
                          ->orWhere(function($q) use ($startTime, $endTime) {
                              $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                          });
                })
                ->exists();

            if ($conflicts) {
                return response()->json([
                    'message' => 'Time slot conflicts with existing booking',
                ], 409);
            }

            // Update booking
            $booking->update($validated);

            // Create status history if status changed
            if (isset($validated['status']) && $validated['status'] !== $booking->getOriginal('status')) {
                try {
                    $booking->statusHistory()->create([
                        'status' => $validated['status'],
                        'changed_by' => auth()->id(),
                        'notes' => $user->isAdmin() ? 'Booking modified by admin' : 'Booking modified by user',
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
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        // Create status history
        try {
            $booking->statusHistory()->create([
                'status' => 'rejected',
                'changed_by' => auth()->id(),
                'notes' => 'Booking rejected by admin. Reason: ' . $request->reason,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to create booking status history: ' . $e->getMessage());
        }

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
            'cancellation_reason' => $request->reason,
        ]);
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
