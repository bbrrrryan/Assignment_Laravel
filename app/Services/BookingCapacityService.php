<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Facility;
use Carbon\Carbon;

class BookingCapacityService
{
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
    public function checkCapacityByTimeSegments(
        Facility $facility,
        int $facilityId,
        string $bookingDate,
        string $startTime,
        string $endTime,
        int $expectedAttendees,
        ?int $excludeBookingId = null
    ): array {
        // Get all pending and approved bookings for this facility on this date
        $query = Booking::where('facility_id', $facilityId)
            ->whereDate('booking_date', $bookingDate)
            ->whereIn('status', ['pending', 'approved']);
        
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }
        
        $bookings = $query->get();

        // Parse the requested time range
        $requestStart = Carbon::parse($startTime);
        $requestEnd = Carbon::parse($endTime);
        
        // Create hourly time segments for the requested time range
        $timeSegments = $this->createTimeSegments($requestStart, $requestEnd);
        
        // Check each time segment
        foreach ($timeSegments as $segment) {
            $segmentStart = $segment['start'];
            $segmentEnd = $segment['end'];
            
            // Find all bookings that overlap with this segment
            $overlappingBookings = $this->getOverlappingBookings($bookings, $segmentStart, $segmentEnd);
            
            // Calculate total attendees in this segment
            $totalAttendees = $this->calculateTotalAttendees($overlappingBookings, $facility);
            
            // For the new booking, if facility has enable_multi_attendees, it occupies full capacity
            $newBookingAttendees = $facility->enable_multi_attendees 
                ? $facility->capacity 
                : $expectedAttendees;
            
            $totalAttendees += $newBookingAttendees;
            
            // Check if this segment would exceed capacity
            $capacityCheck = $this->checkSegmentCapacity(
                $facility,
                $overlappingBookings,
                $totalAttendees,
                $segmentStart,
                $segmentEnd
            );
            
            if (!$capacityCheck['available']) {
                return $capacityCheck;
            }
        }
        
        return [
            'available' => true,
            'message' => 'Capacity check passed'
        ];
    }

    /**
     * Create hourly time segments for the requested time range
     */
    private function createTimeSegments(Carbon $requestStart, Carbon $requestEnd): array
    {
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
        
        return $timeSegments;
    }

    /**
     * Get bookings that overlap with a time segment
     */
    private function getOverlappingBookings($bookings, Carbon $segmentStart, Carbon $segmentEnd)
    {
        return $bookings->filter(function($booking) use ($segmentStart, $segmentEnd) {
            $bookingStart = Carbon::parse($booking->start_time);
            $bookingEnd = Carbon::parse($booking->end_time);
            
            // Check if booking overlaps with this segment
            return $bookingStart < $segmentEnd && $bookingEnd > $segmentStart;
        });
    }

    /**
     * Calculate total attendees from overlapping bookings
     */
    private function calculateTotalAttendees($overlappingBookings, Facility $facility): int
    {
        return $overlappingBookings->sum(function($booking) use ($facility) {
            // If this facility has enable_multi_attendees, each booking occupies full capacity
            if ($facility->enable_multi_attendees) {
                return $facility->capacity;
            }
            // Otherwise, use expected_attendees
            return $booking->expected_attendees ?? 1;
        });
    }

    /**
     * Check if a segment would exceed capacity
     */
    private function checkSegmentCapacity(
        Facility $facility,
        $overlappingBookings,
        int $totalAttendees,
        Carbon $segmentStart,
        Carbon $segmentEnd
    ): array {
        $segmentTimeStr = $segmentStart->format('H:i') . ' - ' . $segmentEnd->format('H:i');
        
        if ($facility->enable_multi_attendees) {
            // If multi_attendees is enabled, check if there's already a booking in this segment
            if ($overlappingBookings->count() > 0) {
                return [
                    'available' => false,
                    'message' => "This time slot ({$segmentTimeStr}) is already booked. " .
                                "When multi-attendees is enabled, each booking occupies the entire facility capacity."
                ];
            }
        } else {
            // Normal capacity check for non-multi-attendees facilities
            if ($totalAttendees > $facility->capacity) {
                $existingAttendees = $totalAttendees - ($facility->enable_multi_attendees ? $facility->capacity : 1);
                
                return [
                    'available' => false,
                    'message' => "Booking would exceed facility capacity during {$segmentTimeStr}. " .
                                "Capacity: {$facility->capacity}, " .
                                "Current attendees: {$existingAttendees}, " .
                                "After booking: {$totalAttendees}"
                ];
            }
        }
        
        return ['available' => true];
    }

    /**
     * Check max booking hours limit for user on a specific date
     * This method calculates total hours from booking_slots table to ensure accuracy
     */
    public function checkMaxBookingHours(
        int $userId,
        int $facilityId,
        string $bookingDate,
        int $durationHours,
        int $maxBookingHours,
        ?int $excludeBookingId = null
    ): array {
        // Get all bookings for this user, facility, and date
        $bookingsQuery = Booking::where('user_id', $userId)
            ->where('facility_id', $facilityId)
            ->whereDate('booking_date', $bookingDate)
            ->whereIn('status', ['pending', 'approved']);
        
        if ($excludeBookingId) {
            $bookingsQuery->where('id', '!=', $excludeBookingId);
        }
        
        $userBookingsOnDate = $bookingsQuery->pluck('id');
        
        // Calculate total hours from booking_slots table for accurate calculation
        // This ensures we count all slots, not just the duration_hours field
        $totalUserBookingHours = BookingSlot::whereIn('booking_id', $userBookingsOnDate)
            ->whereDate('slot_date', $bookingDate)
            ->sum('duration_hours');
        
        $totalAfterBooking = $totalUserBookingHours + $durationHours;
        
        if ($totalAfterBooking > $maxBookingHours) {
            return [
                'available' => false,
                'message' => "You have reached the maximum booking limit for this facility on this date. " .
                            "Maximum allowed: {$maxBookingHours} hour(s), " .
                            "Your current bookings: {$totalUserBookingHours} hour(s), " .
                            "After this booking: {$totalAfterBooking} hour(s)"
            ];
        }
        
        return ['available' => true];
    }
}

