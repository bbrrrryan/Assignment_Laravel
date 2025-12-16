<?php
/**
 * Author: Liew Zi Li
 * Module: Booking Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\Booking;
use Carbon\Carbon;

class BookingFactory
{
    /**
     * Create a booking with status string
     * 
     * @param int $userId
     * @param int $facilityId
     * @param string $bookingDate Date in Y-m-d format
     * @param string $startTime Time in H:i format
     * @param string $endTime Time in H:i format
     * @param string $purpose
     * @param string $status Booking status ('pending', 'approved', 'rejected', 'cancelled', 'completed')
     * @param int|null $expectedAttendees
     * @param int|null $approvedBy
     * @param string|null $rejectionReason
     * @param string|null $cancellationReason
     * @return Booking
     */
    public static function makeBooking(
        $userId,
        $facilityId,
        $bookingDate,
        $startTime,
        $endTime,
        $purpose,
        $status = 'pending',
        $expectedAttendees = null,
        $approvedBy = null,
        $rejectionReason = null,
        $cancellationReason = null
    ) {
        // Normalize status name
        $statusName = strtolower(trim($status));
        
        // Validate status - using simple if-else
        if ($statusName === 'pending') {
            $statusValue = 'pending';
        } elseif ($statusName === 'approved') {
            $statusValue = 'approved';
        } elseif ($statusName === 'rejected') {
            $statusValue = 'rejected';
        } elseif ($statusName === 'cancelled') {
            $statusValue = 'cancelled';
        } elseif ($statusName === 'completed') {
            $statusValue = 'completed';
        } else {
            // Default to pending if invalid
            $statusValue = 'pending';
        }

        // Calculate duration in hours
        $startDateTime = Carbon::parse($bookingDate . ' ' . $startTime);
        $endDateTime = Carbon::parse($bookingDate . ' ' . $endTime);
        $durationHours = $startDateTime->diffInHours($endDateTime);

        // Format time to datetime format (model casts to datetime)
        $startTimeFormatted = $bookingDate . ' ' . $startTime;
        if (strlen($startTime) === 5) { // H:i format
            $startTimeFormatted = $bookingDate . ' ' . $startTime . ':00';
        }
        
        $endTimeFormatted = $bookingDate . ' ' . $endTime;
        if (strlen($endTime) === 5) { // H:i format
            $endTimeFormatted = $bookingDate . ' ' . $endTime . ':00';
        }

        // Prepare booking data
        $bookingData = [
            'user_id' => $userId,
            'facility_id' => $facilityId,
            'booking_date' => $bookingDate,
            'start_time' => $startTimeFormatted,
            'end_time' => $endTimeFormatted,
            'duration_hours' => $durationHours,
            'purpose' => $purpose,
            'expected_attendees' => $expectedAttendees ?? 1,
            'status' => $statusValue,
        ];

        // Add status-specific fields
        if ($statusValue === 'approved' && $approvedBy) {
            $bookingData['approved_by'] = $approvedBy;
            $bookingData['approved_at'] = now();
        }

        if ($statusValue === 'rejected' && $rejectionReason) {
            $bookingData['rejection_reason'] = $rejectionReason;
        }

        if ($statusValue === 'cancelled') {
            $bookingData['cancelled_at'] = now();
            if ($cancellationReason) {
                $bookingData['cancellation_reason'] = $cancellationReason;
            }
        }

        return Booking::create($bookingData);
    }
}

