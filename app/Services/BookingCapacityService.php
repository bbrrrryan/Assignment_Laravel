<?php
/**
 * Author: Low Kim Hong
 */

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Facility;
use Carbon\Carbon;

class BookingCapacityService
{
    
    public function checkCapacityByTimeSegments(
        Facility $facility,
        int $facilityId,
        string $bookingDate,
        string $startTime,
        string $endTime,
        int $expectedAttendees,
        ?int $excludeBookingId = null
    ): array {
        $query = Booking::where('facility_id', $facilityId)
            ->whereDate('booking_date', $bookingDate)
            ->whereIn('status', ['pending', 'approved']);
        
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }
        
        $bookings = $query->get();

        $requestStart = Carbon::parse($startTime);
        $requestEnd = Carbon::parse($endTime);
        
        $timeSegments = $this->createTimeSegments($requestStart, $requestEnd);
        
        foreach ($timeSegments as $segment) {
            $segmentStart = $segment['start'];
            $segmentEnd = $segment['end'];
            
            $overlappingBookings = $this->getOverlappingBookings($bookings, $segmentStart, $segmentEnd);
            
            $totalAttendees = $this->calculateTotalAttendees($overlappingBookings, $facility);
            
            $newBookingAttendees = ($facility->enable_multi_attendees || $this->isFullCapacityFacilityType($facility->type))
                ? $facility->capacity 
                : $expectedAttendees;
            
            $totalAttendees += $newBookingAttendees;
            
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

    private function createTimeSegments(Carbon $requestStart, Carbon $requestEnd): array
    {
        $timeSegments = [];
        $current = $requestStart->copy();
        
        while ($current < $requestEnd) {
            $segmentStart = $current->copy();
            $segmentEnd = $current->copy()->addHour();
            
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

    private function getOverlappingBookings($bookings, Carbon $segmentStart, Carbon $segmentEnd)
    {
        return $bookings->filter(function($booking) use ($segmentStart, $segmentEnd) {
            $bookingStart = Carbon::parse($booking->start_time);
            $bookingEnd = Carbon::parse($booking->end_time);
            
            return $bookingStart < $segmentEnd && $bookingEnd > $segmentStart;
        });
    }

    private function calculateTotalAttendees($overlappingBookings, Facility $facility): int
    {
        return $overlappingBookings->sum(function($booking) use ($facility) {
            if ($facility->enable_multi_attendees || $this->isFullCapacityFacilityType($facility->type)) {
                return $facility->capacity;
            }
            return $booking->expected_attendees ?? 1;
        });
    }

    private function checkSegmentCapacity(
        Facility $facility,
        $overlappingBookings,
        int $totalAttendees,
        Carbon $segmentStart,
        Carbon $segmentEnd
    ): array {
        $segmentTimeStr = $segmentStart->format('H:i') . ' - ' . $segmentEnd->format('H:i');
        
        $requiresFullCapacity = $facility->enable_multi_attendees || $this->isFullCapacityFacilityType($facility->type);
        
        if ($requiresFullCapacity) {
            if ($overlappingBookings->count() > 0) {
                $facilityTypeMsg = $this->isFullCapacityFacilityType($facility->type) 
                    ? "This facility type ({$facility->type}) requires the entire capacity to be occupied per booking."
                    : "When multi-attendees is enabled, each booking occupies the entire facility capacity.";
                return [
                    'available' => false,
                    'message' => "This time slot ({$segmentTimeStr}) is already booked. " . $facilityTypeMsg
                ];
            }
        } else {
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

    private function isFullCapacityFacilityType(string $type): bool
    {
        return in_array($type, ['classroom', 'auditorium', 'laboratory']);
    }

    public function checkMaxBookingHours(
        int $userId,
        int $facilityId,
        string $bookingDate,
        int $durationHours,
        int $maxBookingHours,
        ?int $excludeBookingId = null
    ): array {

        $bookingsQuery = Booking::where('user_id', $userId)
            ->where('facility_id', $facilityId)
            ->whereDate('booking_date', $bookingDate)
            ->whereIn('status', ['pending', 'approved']);
        
        if ($excludeBookingId) {
            $bookingsQuery->where('id', '!=', $excludeBookingId);
        }
        
        $userBookingsOnDate = $bookingsQuery->pluck('id');

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

