<?php
/**
 * Author: Boo Kai Jie
 */

namespace App\Factories;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;
use App\Models\LoyaltyRule;

class LoyaltyFactory
{
   
    public static function makeLoyaltyPoint($userId, $points, $actionType, $description = null, $relatedId = null, $relatedType = null)
    {
        $actionTypeName = strtolower(trim($actionType));
        
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

   
    public static function makeReward($name, $pointsRequired, $rewardType, $description = null, $imageUrl = null, $stockQuantity = null, $isActive = true)
    {
        $normalizedType = strtolower(trim($rewardType));
        
        if ($normalizedType === 'certificate') {
            $typeName = 'certificate';
        } elseif ($normalizedType === 'badge') {
            $typeName = 'badge';
        } elseif ($normalizedType === 'privilege') {
            $typeName = 'privilege';
        } elseif ($normalizedType === 'physical') {
            $typeName = 'physical';
        } else {
            $typeName = 'certificate';
        }

        $points = max(1, (int)$pointsRequired);

        $stock = null;
        if ($stockQuantity !== null) {
            $stock = max(0, (int)$stockQuantity);
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

   
    public static function makeCertificate($userId, $title, $rewardId = null, $description = null, $issuedDate = null, $issuedBy = null, $status = 'approved')
    {
        $normalizedStatus = strtolower(trim($status));
        
        if ($normalizedStatus === 'pending') {
            $statusName = 'pending';
        } elseif ($normalizedStatus === 'approved') {
            $statusName = 'approved';
        } elseif ($normalizedStatus === 'rejected') {
            $statusName = 'rejected';
        } else {
            $statusName = 'approved';
        }

        $certificateNumber = 'CERT-' . strtoupper(uniqid());

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

   
    public static function makeLoyaltyRule($actionType, $name, $points, $description = null, $isActive = true, $conditions = null)
    {
        $normalizedActionType = strtolower(trim($actionType));
        
        $pointsValue = max(0, (int)$points);

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

