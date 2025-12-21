<?php
/**
 * Author: Boo Kai Jie
 */

namespace App\Factories;

use App\Models\Feedback;

class FeedbackFactory
{
    
    public static function makeFeedback($userId, $type, $subject, $message, $rating, $facilityId = null, $bookingId = null, $image = null, $status = 'pending')
    {
        $normalizedType = strtolower(trim($type));
        
        if ($normalizedType === 'complaint') {
            $typeName = 'complaint';
        } elseif ($normalizedType === 'suggestion') {
            $typeName = 'suggestion';
        } elseif ($normalizedType === 'compliment') {
            $typeName = 'compliment';
        } elseif ($normalizedType === 'general') {
            $typeName = 'general';
        } else {
            $typeName = 'general';
        }

        $normalizedStatus = strtolower(trim($status));
        
        if ($normalizedStatus === 'pending') {
            $statusName = 'pending';
        } elseif ($normalizedStatus === 'under_review' || $normalizedStatus === 'reviewed') {
            $statusName = 'under_review';
        } elseif ($normalizedStatus === 'resolved') {
            $statusName = 'resolved';
        } elseif ($normalizedStatus === 'rejected') {
            $statusName = 'rejected';
        } elseif ($normalizedStatus === 'blocked') {
            $statusName = 'blocked';
        } else {
            $statusName = 'pending';
        }

        $ratingValue = (int)$rating;
        if ($ratingValue < 1) {
            $ratingValue = 1;
        } elseif ($ratingValue > 5) {
            $ratingValue = 5;
        }

        return Feedback::create([
            'user_id' => $userId,
            'facility_id' => $facilityId,
            'booking_id' => $bookingId,
            'type' => $typeName,
            'subject' => $subject,
            'message' => $message,
            'image' => $image,
            'rating' => $ratingValue,
            'status' => $statusName,
        ]);
    }
}

