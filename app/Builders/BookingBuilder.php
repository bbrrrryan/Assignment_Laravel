<?php
/**
 * Author: Low Kim Hong
 * Module: Booking Management Module
 * Design Pattern: Builder Pattern (Replacing Factory Pattern)
 * 
 * This builder provides a fluent interface for creating Booking objects
 * with improved readability and maintainability compared to the Factory pattern.
 */

namespace App\Builders;

use App\Models\Booking;
use App\Models\BookingSlot;
use Carbon\Carbon;
use InvalidArgumentException;

class BookingBuilder
{
    protected $userId;
    protected $facilityId;
    protected $bookingDate;
    protected $startTime;
    protected $endTime;
    protected $purpose;
    protected $status = 'pending';
    protected $expectedAttendees = 1;
    protected $approvedBy = null;
    protected $rejectionReason = null;
    protected $cancellationReason = null;
    protected $durationHours = null;
    protected $slots = [];

    public function forUser(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function forFacility(int $facilityId): self
    {
        $this->facilityId = $facilityId;
        return $this;
    }

    public function onDate(string $bookingDate): self
    {
        $this->bookingDate = $bookingDate;
        return $this;
    }

    public function fromTime(string $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function toTime(string $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function withPurpose(string $purpose): self
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function withStatus(string $status): self
    {
        $normalizedStatus = $this->normalizeStatus($status);
        $this->status = $normalizedStatus;
        return $this;
    }

    public function asPending(): self
    {
        $this->status = 'pending';
        return $this;
    }

    public function asApproved(?int $approvedBy = null): self
    {
        $this->status = 'approved';
        if ($approvedBy !== null) {
            $this->approvedBy = $approvedBy;
        }
        return $this;
    }

    public function asRejected(?string $reason = null): self
    {
        $this->status = 'rejected';
        if ($reason !== null) {
            $this->rejectionReason = $reason;
        }
        return $this;
    }

    public function asCancelled(?string $reason = null): self
    {
        $this->status = 'cancelled';
        if ($reason !== null) {
            $this->cancellationReason = $reason;
        }
        return $this;
    }

    public function asCompleted(): self
    {
        $this->status = 'completed';
        return $this;
    }

    public function withExpectedAttendees(?int $expectedAttendees): self
    {
        $this->expectedAttendees = $expectedAttendees ?? 1;
        return $this;
    }

    public function approvedBy(int $approvedBy): self
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function withRejectionReason(string $reason): self
    {
        $this->rejectionReason = $reason;
        return $this;
    }

    public function withCancellationReason(string $reason): self
    {
        $this->cancellationReason = $reason;
        return $this;
    }

    public function withDuration(float $durationHours): self
    {
        $this->durationHours = $durationHours;
        return $this;
    }

    public function addSlot(string $date, string $startTime, string $endTime, ?float $durationHours = null): self
    {
        if ($durationHours === null) {
            $startDateTime = Carbon::parse($date . ' ' . $startTime);
            $endDateTime = Carbon::parse($date . ' ' . $endTime);
            $durationHours = $startDateTime->diffInHours($endDateTime);
        }

        $this->slots[] = [
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $durationHours,
        ];

        return $this;
    }

    public function addSlots(array $slots): self
    {
        foreach ($slots as $slot) {
            $date = $slot['date'] ?? $slot['slot_date'] ?? null;
            $startTime = $slot['start_time'] ?? null;
            $endTime = $slot['end_time'] ?? null;
            $duration = $slot['duration'] ?? $slot['duration_hours'] ?? null;

            if ($date && $startTime && $endTime) {
                $this->addSlot($date, $startTime, $endTime, $duration);
            }
        }

        return $this;
    }

    public function build(): Booking
    {
        $this->validateRequiredFields();

        $durationHours = $this->durationHours ?? $this->calculateDuration();
        $startTimeFormatted = $this->formatTime($this->bookingDate, $this->startTime);
        $endTimeFormatted = $this->formatTime($this->bookingDate, $this->endTime);

        $bookingData = [
            'user_id' => $this->userId,
            'facility_id' => $this->facilityId,
            'booking_date' => $this->bookingDate,
            'start_time' => $startTimeFormatted,
            'end_time' => $endTimeFormatted,
            'duration_hours' => $durationHours,
            'purpose' => $this->purpose,
            'expected_attendees' => $this->expectedAttendees,
            'status' => $this->status,
        ];

        $this->addStatusSpecificFields($bookingData);
        $booking = Booking::create($bookingData);

        if (!empty($this->slots)) {
            $this->createBookingSlots($booking);
        }

        return $booking;
    }

    protected function createBookingSlots(Booking $booking): void
    {
        foreach ($this->slots as $slot) {
            $startTimeInput = $slot['start_time'];
            if (strlen($startTimeInput) > 5 && strpos($startTimeInput, ' ') !== false) {
                $slotStart = Carbon::parse($startTimeInput);
                $startTimeFormatted = $slotStart->format('H:i:s');
            } else {
                if (strlen($startTimeInput) === 5) {
                    $startTimeFormatted = $startTimeInput . ':00';
                } else {
                    $startTimeFormatted = $startTimeInput;
                }
            }

            $endTimeInput = $slot['end_time'];
            if (strlen($endTimeInput) > 5 && strpos($endTimeInput, ' ') !== false) {
                $slotEnd = Carbon::parse($endTimeInput);
                $endTimeFormatted = $slotEnd->format('H:i:s');
            } else {
                if (strlen($endTimeInput) === 5) {
                    $endTimeFormatted = $endTimeInput . ':00';
                } else {
                    $endTimeFormatted = $endTimeInput;
                }
            }

            BookingSlot::create([
                'booking_id' => $booking->id,
                'slot_date' => $slot['date'],
                'start_time' => $startTimeFormatted,
                'end_time' => $endTimeFormatted,
                'duration_hours' => $slot['duration'],
            ]);
        }
    }

    protected function validateRequiredFields(): void
    {
        $required = [
            'userId' => 'User ID',
            'facilityId' => 'Facility ID',
            'bookingDate' => 'Booking date',
            'startTime' => 'Start time',
            'endTime' => 'End time',
            'purpose' => 'Purpose',
        ];

        foreach ($required as $property => $name) {
            if ($this->$property === null) {
                throw new InvalidArgumentException("Required field '{$name}' is not set. Use the appropriate builder method to set it.");
            }
        }
    }

    protected function calculateDuration(): float
    {
        $startDateTime = Carbon::parse($this->bookingDate . ' ' . $this->startTime);
        $endDateTime = Carbon::parse($this->bookingDate . ' ' . $this->endTime);
        
        return $startDateTime->diffInHours($endDateTime);
    }

    protected function formatTime(string $date, string $time): string
    {

        if (strlen($time) > 5 && strpos($time, ' ') !== false) {
            $time = Carbon::parse($time)->format('H:i');
        }

        $formatted = $date . ' ' . $time;
        if (strlen($time) === 5) { 
            $formatted = $date . ' ' . $time . ':00';
        }

        return $formatted;
    }

    protected function normalizeStatus(string $status): string
    {
        $statusName = strtolower(trim($status));
        
        $validStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];
        
        if (!in_array($statusName, $validStatuses)) {
            return 'pending';
        }
        
        return $statusName;
    }

    protected function addStatusSpecificFields(array &$bookingData): void
    {
        switch ($this->status) {
            case 'approved':
                if ($this->approvedBy !== null) {
                    $bookingData['approved_by'] = $this->approvedBy;
                    $bookingData['approved_at'] = now();
                }
                break;

            case 'rejected':
                if ($this->rejectionReason !== null) {
                    $bookingData['rejection_reason'] = $this->rejectionReason;
                }
                break;

            case 'cancelled':
                $bookingData['cancelled_at'] = now();
                if ($this->cancellationReason !== null) {
                    $bookingData['cancellation_reason'] = $this->cancellationReason;
                }
                break;
        }
    }

    public static function create(): self
    {
        return new self();
    }
}

