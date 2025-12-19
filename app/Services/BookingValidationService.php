<?php

namespace App\Services;

use App\Models\Facility;
use Carbon\Carbon;

class BookingValidationService
{
    /**
     * Get validation rules for booking based on facility settings
     */
    public function getValidationRules(Facility $facility): array
    {
        $expectedAttendeesRule = 'nullable|integer|min:1';
        if ($facility->enable_multi_attendees) {
            $expectedAttendeesRule = 'required|integer|min:1';
            if ($facility->max_attendees) {
                $expectedAttendeesRule .= '|max:' . $facility->max_attendees;
            }
        }

        return [
            'facility_id' => 'required|exists:facilities,id',
            'booking_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $bookingDate = Carbon::parse($value)->startOfDay();
                    $today = Carbon::today()->startOfDay();
                    if ($bookingDate->lte($today)) {
                        $fail('You can only book from tomorrow onwards. Please select a future date.');
                    }
                },
            ],
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'purpose' => 'required|string|max:500',
            'expected_attendees' => $expectedAttendeesRule,
            'attendees_passports' => 'nullable|array',
            'attendees_passports.*' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $trimmedValue = trim($value);
                        if (!empty($trimmedValue) && !$this->validateStudentIdFormat($trimmedValue)) {
                            $fail('The attendee passport must be in the format YYWMR##### (e.g., 25WMR00001).');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Normalize expected attendees based on facility settings
     */
    public function normalizeExpectedAttendees(?int $expectedAttendees, Facility $facility): int
    {
        if ($expectedAttendees === null || $expectedAttendees === 0) {
            return $facility->enable_multi_attendees ? 1 : 1;
        }
        return $expectedAttendees;
    }

    /**
     * Parse and normalize datetime formats
     */
    public function parseDateTime(string $dateTime): string
    {
        return Carbon::parse($dateTime)->format('Y-m-d H:i:s');
    }

    /**
     * Validate time range
     */
    public function validateTimeRange(string $startTime, string $endTime): ?string
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        
        if ($end->lte($start)) {
            return 'End time must be after start time';
        }
        
        return null;
    }

    /**
     * Validate booking date is within facility's available days
     */
    public function validateAvailableDay(string $bookingDate, Facility $facility): ?string
    {
        $bookingDateCarbon = Carbon::createFromFormat('Y-m-d', $bookingDate, config('app.timezone'))
            ->setTime(0, 0, 0)
            ->setTimezone(config('app.timezone'));
        $dayOfWeek = strtolower($bookingDateCarbon->format('l'));
        
        if ($facility->available_day && is_array($facility->available_day) && !empty($facility->available_day)) {
            if (!in_array($dayOfWeek, $facility->available_day)) {
                $availableDaysStr = implode(', ', array_map('ucfirst', $facility->available_day));
                return "This facility is not available on {$bookingDateCarbon->format('l, F j, Y')}. Available days: {$availableDaysStr}";
            }
        }
        
        return null;
    }

    /**
     * Validate time is within facility's available time range
     */
    public function validateAvailableTime(string $startTime, string $endTime, Facility $facility): ?string
    {
        $minTime = '08:00';
        $maxTime = '20:00';
        
        if ($facility->available_time && is_array($facility->available_time)) {
            if (isset($facility->available_time['start']) && !empty($facility->available_time['start'])) {
                $minTime = $facility->available_time['start'];
            }
            if (isset($facility->available_time['end']) && !empty($facility->available_time['end'])) {
                $maxTime = $facility->available_time['end'];
            }
        }
        
        $startHour = Carbon::parse($startTime)->format('H:i');
        $endHour = Carbon::parse($endTime)->format('H:i');
        
        if ($startHour < $minTime || $startHour > $maxTime) {
            return "Start time must be between {$minTime} and {$maxTime} (facility operating hours)";
        }
        
        if ($endHour < $minTime || $endHour > $maxTime) {
            return "End time must be between {$minTime} and {$maxTime} (facility operating hours)";
        }
        
        return null;
    }

    /**
     * Validate facility status
     */
    public function validateFacilityStatus(Facility $facility): ?string
    {
        if ($facility->status !== 'available') {
            return 'Facility is not available for booking';
        }
        
        return null;
    }

    /**
     * Validate expected attendees against facility capacity
     */
    public function validateCapacity(int $expectedAttendees, Facility $facility): ?string
    {
        if ($expectedAttendees > $facility->capacity) {
            return 'Expected attendees exceed facility capacity';
        }
        
        if ($facility->enable_multi_attendees && $facility->max_attendees && $expectedAttendees > $facility->max_attendees) {
            return "Expected attendees ({$expectedAttendees}) exceed maximum allowed ({$facility->max_attendees}) for this facility";
        }
        
        return null;
    }

    /**
     * Validate student ID format
     * Format: YYWMR##### (e.g., 25WMR00001)
     * YY: 2-digit year
     * WMR: Fixed prefix
     * #####: 5-digit sequential number
     */
    public function validateStudentIdFormat(string $studentId): bool
    {
        // Pattern: 2 digits, WMR, 5 digits
        $pattern = '/^\d{2}WMR\d{5}$/';
        return preg_match($pattern, $studentId) === 1;
    }
}

