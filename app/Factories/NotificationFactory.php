<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Factories;

use App\Models\Notification;

class NotificationFactory
{
   
    public static function makeNotification($type, $title, $message, $targetAudience, $createdBy = null, $priority = null, $targetUserIds = null, $scheduledAt = null, $expiresAt = null, $isActive = true)
    {
        $notificationType = strtolower(trim($type));
        
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
            $typeName = 'info';
        }

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

