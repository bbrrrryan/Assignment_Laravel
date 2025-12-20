<?php
/**
 * Author: Boo Kai Jie
 * Module: Feedback Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\Feedback;

class FeedbackFactory
{
    /**
     * Create a feedback record
     * 
     * @param int $userId
     * @param string $type ('complaint', 'suggestion', 'compliment', 'general')
     * @param string $subject
     * @param string $message
     * @param int $rating (1-5)
     * @param int|null $facilityId
     * @param int|null $bookingId
     * @param string|null $image (base64 image string)
     * @param string|null $status ('pending', 'under_review', 'resolved', 'rejected', 'blocked')
     * @return Feedback
     */
    public static function makeFeedback($userId, $type, $subject, $message, $rating, $facilityId = null, $bookingId = null, $image = null, $status = 'pending')
    {
        // Normalize feedback type
        $normalizedType = strtolower(trim($type));
        
        // Validate type - using simple if-else
        if ($normalizedType === 'complaint') {
            $typeName = 'complaint';
        } elseif ($normalizedType === 'suggestion') {
            $typeName = 'suggestion';
        } elseif ($normalizedType === 'compliment') {
            $typeName = 'compliment';
        } elseif ($normalizedType === 'general') {
            $typeName = 'general';
        } else {
            // Default to general if invalid
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
            // Default to pending if invalid
            $statusName = 'pending';
        }

        // Validate and normalize rating (1-5)
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

