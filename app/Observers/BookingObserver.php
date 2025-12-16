<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use App\Factories\LoyaltyFactory;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    /**
     * Handle the Booking "updated" event.
     * Checgitk if points should be awarded when booking status is updated
     */
    public function updated(Booking $booking)
    {
        // Get the status before and after update
        $originalStatus = $booking->getOriginal('status');
        $newStatus = $booking->status;

        // Only award points when status changes from 'approved' to 'completed'
        // Only reward manual completion from approved status
        if ($originalStatus === 'approved' && $newStatus === 'completed') {
            $this->awardPointsForCompletedBooking($booking);
        }
    }

    /**
     * Award points for completed booking
     */
    protected function awardPointsForCompletedBooking(Booking $booking)
    {
        try {
            // Dynamically find all loyalty rules related to facility bookings
            // Use prefix matching to automatically identify all booking-related rules without code changes
            // Supported naming patterns: facility_booking_* or booking_*
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
                // Check if points have already been awarded for this rule (prevent duplicate rewards)
                $existingPoint = LoyaltyPoint::where('user_id', $booking->user_id)
                    ->where('action_type', $rule->action_type)
                    ->where('related_id', $booking->id)
                    ->where('related_type', Booking::class)
                    ->first();

                if ($existingPoint) {
                    Log::info("Points already awarded for rule {$rule->action_type} on booking #{$booking->id}");
                    continue;
                }

                // Check rule conditions (if any)
                if (!$this->checkRuleConditions($rule, $booking)) {
                    Log::info("Booking #{$booking->id} does not meet rule conditions for {$rule->action_type}");
                    continue;
                }

                // Create points record
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
            // Log error but do not interrupt booking status update
            Log::error("Failed to award points for booking #{$booking->id}: " . $e->getMessage());
        }
    }

    /**
     * Check if rule conditions are met
     * Can validate based on conditions in the conditions field
     */
    protected function checkRuleConditions(LoyaltyRule $rule, Booking $booking): bool
    {
        // If no conditions are set, default to pass
        if (empty($rule->conditions)) {
            $conditions = [];
        } else {
            $conditions = $rule->conditions;
        }

        // Load facility information
        $facility = $booking->facility;
        if (!$facility) {
            Log::warning("Booking #{$booking->id} has no facility associated");
            return false;
        }

        // Smart matching: If rule name contains specific facility identifier, automatically match facility name or code
        // Example: facility_booking_gym should only match facilities with name/code containing "gym"
        if (!$this->matchFacilityFromRuleName($rule, $facility)) {
            return false;
        }

        // Check explicitly specified facility names in conditions
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

        // Check explicitly specified facility codes in conditions
        if (isset($conditions['facility_codes']) && is_array($conditions['facility_codes'])) {
            $facilityCode = strtolower($facility->code ?? '');
            if (!in_array($facilityCode, array_map('strtolower', $conditions['facility_codes']))) {
                return false;
            }
        }

        // Check facility type
        if (isset($conditions['facility_types']) && is_array($conditions['facility_types'])) {
            $facilityType = $facility->type ?? null;
            if (!in_array($facilityType, $conditions['facility_types'])) {
                return false;
            }
        }

        // Example: Check user role
        if (isset($conditions['user_roles']) && is_array($conditions['user_roles'])) {
            $userRole = $booking->user->role ?? null;
            if (!in_array($userRole, $conditions['user_roles'])) {
                return false;
            }
        }

        // Example: Check booking duration
        if (isset($conditions['min_duration_hours'])) {
            if ($booking->duration_hours < $conditions['min_duration_hours']) {
                return false;
            }
        }

        // Add some built-in rule behaviors based on action_type
        switch ($rule->action_type) {
            case 'facility_booking_first':
                // Only award when user completes booking for the first time
                $previousCompletedCount = Booking::where('user_id', $booking->user_id)
                    ->where('status', 'completed')
                    ->where('id', '!=', $booking->id)
                    ->count();

                if ($previousCompletedCount > 0) {
                    return false;
                }
                break;

            case 'facility_booking_long_duration':
                // If min_duration_hours is not set in conditions, default to 2 hours
                $minDuration = $conditions['min_duration_hours'] ?? 2;
                if ($booking->duration_hours < $minDuration) {
                    return false;
                }
                break;

            default:
                // Other action_types do not have special conditions yet
                break;
        }

        // Can continue adding more condition checks here...

        return true;
    }

    /**
     * Extract facility identifier from rule name and match facility
     * If rule name contains specific facility identifier (e.g., gym, swimming_pool), only match corresponding facilities
     * 
     * @param LoyaltyRule $rule
     * @param Facility $facility
     * @return bool Returns false if rule name contains facility identifier but doesn't match
     */
    protected function matchFacilityFromRuleName(LoyaltyRule $rule, $facility): bool
    {
        $actionType = $rule->action_type;
        
        // Remove common prefixes
        $cleanActionType = preg_replace('/^(facility_booking_|booking_)/', '', $actionType);
        
        // Generic rules (e.g., facility_booking_complete, facility_booking_first) should apply to all facilities
        $genericRules = ['complete', 'first', 'long_duration'];
        if (in_array($cleanActionType, $genericRules)) {
            return true;
        }

        // If rule name contains underscores, try to extract facility identifier
        // Example: facility_booking_gym → gym
        //          booking_swimming_pool → swimming_pool
        $facilityIdentifier = $cleanActionType;
        
        // Convert underscores to spaces for matching
        // Example: swimming_pool → swimming pool
        $facilityName = strtolower($facility->name ?? '');
        $facilityCode = strtolower($facility->code ?? '');
        $searchPatterns = [
            strtolower($facilityIdentifier),
            str_replace('_', ' ', strtolower($facilityIdentifier)),
            str_replace('_', '', strtolower($facilityIdentifier)),
        ];

        // Check if facility name or code contains the identifier
        foreach ($searchPatterns as $pattern) {
            if (stripos($facilityName, $pattern) !== false || 
                stripos($facilityCode, $pattern) !== false) {
                return true;
            }
        }

        // If rule name is not a generic rule and doesn't match facility, do not apply this rule
        Log::info("Rule {$actionType} does not match facility '{$facility->name}' (code: {$facility->code})");
        return false;
    }
}

