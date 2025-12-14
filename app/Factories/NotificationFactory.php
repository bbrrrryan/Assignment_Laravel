<?php
/**
 * Author: Liew Zi Li
 * Module: Notification Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\Notification;

class NotificationFactory
{
    /**
     * Create a notification with type string
     * 
     * @param string $type Notification type ('info', 'warning', 'success', 'error', 'reminder')
     * @param string $title
     * @param string $message
     * @param string $targetAudience
     * @param int|null $createdBy
     * @param string|null $priority
     * @param array|null $targetUserIds
     * @param string|null $scheduledAt
     * @param string|null $expiresAt
     * @param bool $isActive
     * @return Notification
     */
    public static function makeNotification($type, $title, $message, $targetAudience, $createdBy = null, $priority = null, $targetUserIds = null, $scheduledAt = null, $expiresAt = null, $isActive = true)
    {
        // Normalize notification type
        $notificationType = strtolower(trim($type));
        
        // Validate type - using simple if-else
        if ($notificationType === 'info') {
            $typeName = 'info';
        } elseif ($notificationType === 'warning') {
            $typeName = 'warning';
        } elseif ($notificationType === 'success') {
            $typeName = 'success';
        } elseif ($notificationType === 'error') {
            $typeName = 'error';
        } elseif ($notificationType === 'reminder') {
            $typeName = 'reminder';
        } else {
            // Default to info if invalid
            $typeName = 'info';
        }

        // Validate priority - using simple if-else
        $priorityName = null;
        if ($priority !== null) {
            $priorityLower = strtolower(trim($priority));
            if ($priorityLower === 'low' || $priorityLower === 'medium' || $priorityLower === 'high' || $priorityLower === 'urgent') {
                $priorityName = $priorityLower;
            }
        }

        return Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => $typeName,
            'priority' => $priorityName,
            'created_by' => $createdBy,
            'target_audience' => $targetAudience,
            'target_user_ids' => $targetUserIds,
            'scheduled_at' => $scheduledAt,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
        ]);
    }
}

