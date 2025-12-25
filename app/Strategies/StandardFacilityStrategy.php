<?php
/**
 * Author:Ng Jhun Hou
 */
namespace App\Strategies;

use App\Models\Facility;
use Illuminate\Support\Facades\Validator;

class StandardFacilityStrategy implements FacilityValidationStrategy
{
    public function getFacilityType(): string
    {
        return 'standard';
    }

    public function validate(array $data, ?Facility $facility = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:facilities,code' . ($facility ? ',' . $facility->id : ''),
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library,cafeteria,other',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'enable_multi_attendees' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1|lte:capacity|required_if:enable_multi_attendees,1',
            'available_day' => 'nullable|array',
            'available_day.*' => 'nullable|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_time' => 'nullable|array',
            'available_time.start' => 'nullable|string|date_format:H:i',
            'available_time.end' => 'nullable|string|date_format:H:i|after:available_time.start',
            'equipment' => 'nullable',
            'equipment_json' => 'nullable|string',
            'equipment.*' => 'nullable|string',
            'rules' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray()
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    public function getDefaultValues(): array
    {
        return [
            'enable_multi_attendees' => false,
            'max_booking_hours' => 4,
            'status' => 'available',
        ];
    }

    public function processBeforeSave(array $data): array
    {
        if (!isset($data['enable_multi_attendees']) || !$data['enable_multi_attendees']) {
            $data['max_attendees'] = null;
        }

        if (!isset($data['max_booking_hours']) || $data['max_booking_hours'] < 1) {
            $data['max_booking_hours'] = 4;
        }

        return $data;
    }
}

