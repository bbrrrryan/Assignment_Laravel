<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use App\Factories\LoyaltyFactory;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function updated(Booking $booking)
    {
        $originalStatus = $booking->getOriginal('status');
        $newStatus = $booking->status;

        if ($originalStatus === 'approved' && $newStatus === 'completed') {
            $this->awardPointsForCompletedBooking($booking);
        }
    }

    protected function awardPointsForCompletedBooking(Booking $booking)
    {
        try {
            $rules = LoyaltyRule::where('is_active', true)
                ->where(function($query) {
                    $query->where('action_type', 'like', 'facility_booking%')
                          ->orWhere('action_type', 'like', 'booking_%');
                })
                ->get();

            if ($rules->isEmpty()) {
                Log::info("No active loyalty rules found for facility booking actions");
                return;
            }

            foreach ($rules as $rule) {
                $existingPoint = LoyaltyPoint::where('user_id', $booking->user_id)
                    ->where('action_type', $rule->action_type)
                    ->where('related_id', $booking->id)
                    ->where('related_type', Booking::class)
                    ->first();

                if ($existingPoint) {
                    Log::info("Points already awarded for rule {$rule->action_type} on booking #{$booking->id}");
                    continue;
                }

                if (!$this->checkRuleConditions($rule, $booking)) {
                    Log::info("Booking #{$booking->id} does not meet rule conditions for {$rule->action_type}");
                    continue;
                }

                LoyaltyFactory::makeLoyaltyPoint(
                    $booking->user_id,
                    $rule->points,
                    $rule->action_type,
                    $rule->description ?? "Facility booking ({$rule->action_type}): Booking #{$booking->id}",
                    $booking->id,
                    Booking::class
                );

                Log::info("Awarded {$rule->points} points to user #{$booking->user_id} for {$rule->action_type} on booking #{$booking->id}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to award points for booking #{$booking->id}: " . $e->getMessage());
        }
    }

    protected function checkRuleConditions(LoyaltyRule $rule, Booking $booking): bool
    {
        if (empty($rule->conditions)) {
            $conditions = [];
        } else {
            $conditions = $rule->conditions;
        }

        $facility = $booking->facility;
        if (!$facility) {
            Log::warning("Booking #{$booking->id} has no facility associated");
            return false;
        }

        if (!$this->matchFacilityFromRuleName($rule, $facility)) {
            return false;
        }

        if (isset($conditions['facility_names']) && is_array($conditions['facility_names'])) {
            $facilityName = strtolower($facility->name ?? '');
            $facilityCode = strtolower($facility->code ?? '');
            $matches = false;
            foreach ($conditions['facility_names'] as $namePattern) {
                if (stripos($facilityName, strtolower($namePattern)) !== false || 
                    stripos($facilityCode, strtolower($namePattern)) !== false) {
                    $matches = true;
                    break;
                }
            }
            if (!$matches) {
                return false;
            }
        }

        if (isset($conditions['facility_codes']) && is_array($conditions['facility_codes'])) {
            $facilityCode = strtolower($facility->code ?? '');
            if (!in_array($facilityCode, array_map('strtolower', $conditions['facility_codes']))) {
                return false;
            }
        }

        if (isset($conditions['facility_types']) && is_array($conditions['facility_types'])) {
            $facilityType = $facility->type ?? null;
            if (!in_array($facilityType, $conditions['facility_types'])) {
                return false;
            }
        }

        if (isset($conditions['user_roles']) && is_array($conditions['user_roles'])) {
            $userRole = $booking->user->role ?? null;
            if (!in_array($userRole, $conditions['user_roles'])) {
                return false;
            }
        }

        if (isset($conditions['min_duration_hours'])) {
            if ($booking->duration_hours < $conditions['min_duration_hours']) {
                return false;
            }
        }

        switch ($rule->action_type) {
            case 'facility_booking_first':
                $previousCompletedCount = Booking::where('user_id', $booking->user_id)
                    ->where('status', 'completed')
                    ->where('id', '!=', $booking->id)
                    ->count();

                if ($previousCompletedCount > 0) {
                    return false;
                }
                break;

            case 'facility_booking_long_duration':
                $minDuration = $conditions['min_duration_hours'] ?? 2;
                if ($booking->duration_hours < $minDuration) {
                    return false;
                }
                break;

            default:
                break;
        }


        return true;
    }

    protected function matchFacilityFromRuleName(LoyaltyRule $rule, $facility): bool
    {
        $actionType = $rule->action_type;
        
        $cleanActionType = preg_replace('/^(facility_booking_|booking_)/', '', $actionType);
        
        $genericRules = ['complete', 'first', 'long_duration'];
        if (in_array($cleanActionType, $genericRules)) {
            return true;
        }

        $facilityIdentifier = $cleanActionType;
        
        $facilityName = strtolower($facility->name ?? '');
        $facilityCode = strtolower($facility->code ?? '');
        $searchPatterns = [
            strtolower($facilityIdentifier),
            str_replace('_', ' ', strtolower($facilityIdentifier)),
            str_replace('_', '', strtolower($facilityIdentifier)),
        ];

        foreach ($searchPatterns as $pattern) {
            if (stripos($facilityName, $pattern) !== false || 
                stripos($facilityCode, $pattern) !== false) {
                return true;
            }
        }

        Log::info("Rule {$actionType} does not match facility '{$facility->name}' (code: {$facility->code})");
        return false;
    }
}

