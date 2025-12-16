<?php
/**
 * Author: Boo Kai Jie
 * Module: Loyalty Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;
use App\Models\LoyaltyRule;

class LoyaltyFactory
{
    /**
     * Create a loyalty point record
     * 
     * @param int $userId
     * @param int $points (can be positive or negative)
     * @param string $actionType
     * @param string|null $description
     * @param int|null $relatedId
     * @param string|null $relatedType
     * @return LoyaltyPoint
     */
    public static function makeLoyaltyPoint($userId, $points, $actionType, $description = null, $relatedId = null, $relatedType = null)
    {
        // Normalize action type (convert to lowercase and trim whitespace)
        // Note: This factory supports ANY custom action_type - not limited to a fixed list
        // Common action types include:
        // - 'facility_booking', 'facility_booking_complete', 'facility_booking_first'
        // - 'feedback_submission', 'feedback_resolved'
        // - 'reward_redemption', 'redemption_refund'
        // - 'points_awarded', 'points_deducted', 'manual_adjustment'
        // - Custom action types like 'event_attendance', 'special_achievement', etc.
        $actionTypeName = strtolower(trim($actionType));
        
        // Generate default description if not provided
        if ($description === null || $description === '') {
            if ($points > 0) {
                $description = "Points awarded for: {$actionTypeName}";
            } else {
                $description = "Points deducted for: {$actionTypeName}";
            }
        }

        return LoyaltyPoint::create([
            'user_id' => $userId,
            'points' => $points,
            'action_type' => $actionTypeName,
            'description' => $description,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
        ]);
    }

    /**
     * Create a reward
     * 
     * @param string $name
     * @param int $pointsRequired
     * @param string $rewardType ('certificate', 'badge', 'privilege', 'physical')
     * @param string|null $description
     * @param string|null $imageUrl (can be base64 or URL)
     * @param int|null $stockQuantity
     * @param bool $isActive
     * @return Reward
     */
    public static function makeReward($name, $pointsRequired, $rewardType, $description = null, $imageUrl = null, $stockQuantity = null, $isActive = true)
    {
        // Normalize reward type
        $normalizedType = strtolower(trim($rewardType));
        
        // Validate reward type - using simple if-else
        if ($normalizedType === 'certificate') {
            $typeName = 'certificate';
        } elseif ($normalizedType === 'badge') {
            $typeName = 'badge';
        } elseif ($normalizedType === 'privilege') {
            $typeName = 'privilege';
        } elseif ($normalizedType === 'physical') {
            $typeName = 'physical';
        } else {
            // Default to certificate if invalid
            $typeName = 'certificate';
        }

        // Validate points required
        $points = max(1, (int)$pointsRequired); // Ensure at least 1 point

        // Validate stock quantity
        $stock = null;
        if ($stockQuantity !== null) {
            $stock = max(0, (int)$stockQuantity); // Ensure non-negative
        }

        return Reward::create([
            'name' => $name,
            'description' => $description,
            'points_required' => $points,
            'reward_type' => $typeName,
            'image_url' => $imageUrl,
            'stock_quantity' => $stock,
            'is_active' => $isActive,
        ]);
    }

    /**
     * Create a certificate
     * 
     * @param int $userId
     * @param string $title
     * @param int|null $rewardId
     * @param string|null $description
     * @param string|null $issuedDate
     * @param int|null $issuedBy
     * @param string|null $status ('pending', 'approved', 'rejected')
     * @return Certificate
     */
    public static function makeCertificate($userId, $title, $rewardId = null, $description = null, $issuedDate = null, $issuedBy = null, $status = 'approved')
    {
        // Normalize status
        $normalizedStatus = strtolower(trim($status));
        
        // Validate status - using simple if-else
        if ($normalizedStatus === 'pending') {
            $statusName = 'pending';
        } elseif ($normalizedStatus === 'approved') {
            $statusName = 'approved';
        } elseif ($normalizedStatus === 'rejected') {
            $statusName = 'rejected';
        } else {
            // Default to approved if invalid
            $statusName = 'approved';
        }

        // Generate certificate number
        $certificateNumber = 'CERT-' . strtoupper(uniqid());

        // Use current date if issued date not provided
        $issuedDateValue = $issuedDate ?? now();

        return Certificate::create([
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'certificate_number' => $certificateNumber,
            'title' => $title,
            'description' => $description,
            'issued_date' => $issuedDateValue,
            'issued_by' => $issuedBy ? (string)$issuedBy : null,
            'status' => $statusName,
        ]);
    }

    /**
     * Create a loyalty rule
     * 
     * @param string $actionType (unique identifier for the rule)
     * @param string $name (display name)
     * @param int $points
     * @param string|null $description
     * @param bool $isActive
     * @param array|null $conditions
     * @return LoyaltyRule
     */
    public static function makeLoyaltyRule($actionType, $name, $points, $description = null, $isActive = true, $conditions = null)
    {
        // Normalize action type
        $normalizedActionType = strtolower(trim($actionType));
        
        // Validate points
        $pointsValue = max(0, (int)$points); // Ensure non-negative

        return LoyaltyRule::create([
            'action_type' => $normalizedActionType,
            'name' => $name,
            'description' => $description,
            'points' => $pointsValue,
            'is_active' => $isActive,
            'conditions' => $conditions,
        ]);
    }
}

