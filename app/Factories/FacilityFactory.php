<?php
/**
 * Author: Ng Jhun Hou
 * Module: Facility Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\Facility;

class FacilityFactory
{
    public static function makeFacility(
        $name,
        $code,
        $type,
        $location,
        $capacity,
        $description = null,
        $status = null,
        $imageUrl = null,
        $maxBookingHours = null,
        $enableMultiAttendees = null,
        $maxAttendees = null,
        $availableDay = null,
        $availableTime = null,
        $equipment = null,
        $rules = null
    ) {
        // Normalize facility type
        $facilityType = strtolower(trim($type));
        
        // Validate type - using simple if-else
        if ($facilityType === 'classroom') {
            $typeName = 'classroom';
        } elseif ($facilityType === 'laboratory') {
            $typeName = 'laboratory';
        } elseif ($facilityType === 'sports') {
            $typeName = 'sports';
        } elseif ($facilityType === 'auditorium') {
            $typeName = 'auditorium';
        } elseif ($facilityType === 'library') {
            $typeName = 'library';
        } else {
            // Default to classroom if invalid
            $typeName = 'classroom';
        }

        // Normalize status name
        $statusName = $status ? strtolower(trim($status)) : null;
        
        // Validate status - using simple if-else
        if ($statusName === 'available') {
            $statusValue = 'available';
        } elseif ($statusName === 'maintenance') {
            $statusValue = 'maintenance';
        } elseif ($statusName === 'unavailable') {
            $statusValue = 'unavailable';
        } elseif ($statusName === 'reserved') {
            $statusValue = 'reserved';
        } else {
            // Default to available if invalid or null
            $statusValue = 'available';
        }

        // Handle available_day - ensure it's an array or null
        $availableDayValue = null;
        if ($availableDay !== null) {
            if (is_array($availableDay) && !empty($availableDay)) {
                $availableDayValue = array_values(array_filter($availableDay));
                if (empty($availableDayValue)) {
                    $availableDayValue = null;
                }
            }
        }

        // Handle available_time - ensure it has start and end
        $availableTimeValue = null;
        if ($availableTime !== null && is_array($availableTime)) {
            if (!empty($availableTime['start']) && !empty($availableTime['end'])) {
                $availableTimeValue = [
                    'start' => $availableTime['start'],
                    'end' => $availableTime['end'],
                ];
            }
        }

        // Handle equipment - ensure it's an array or null
        $equipmentValue = null;
        if ($equipment !== null) {
            if (is_array($equipment)) {
                $equipmentValue = $equipment;
            } elseif (is_string($equipment)) {
                // Try to decode JSON string
                $decoded = json_decode($equipment, true);
                $equipmentValue = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            }
        }

        // Prepare facility data
        $facilityData = [
            'name' => $name,
            'code' => $code,
            'type' => $typeName,
            'location' => $location,
            'capacity' => $capacity,
            'description' => $description,
            'status' => $statusValue,
            'image_url' => $imageUrl,
            'available_day' => $availableDayValue,
            'available_time' => $availableTimeValue,
            'equipment' => $equipmentValue,
            'rules' => $rules,
        ];

        // Set default values for boolean and integer fields
        $facilityData['max_booking_hours'] = $maxBookingHours ?? 4;
        $facilityData['enable_multi_attendees'] = $enableMultiAttendees ?? false;
        
        // If multi-attendees is disabled, set max_attendees to null
        if (!($facilityData['enable_multi_attendees'])) {
            $facilityData['max_attendees'] = null;
        } else {
            $facilityData['max_attendees'] = $maxAttendees ?? null;
        }

        // Set is_deleted to false by default
        $facilityData['is_deleted'] = false;

        return Facility::create($facilityData);
    }
}

