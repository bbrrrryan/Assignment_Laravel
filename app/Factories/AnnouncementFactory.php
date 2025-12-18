<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Factories;

use App\Models\Announcement;

class AnnouncementFactory
{
    public static function makeAnnouncement($type, $title, $content, $targetAudience, $createdBy = null, $priority = null, $targetUserIds = null, $publishedAt = null, $expiresAt = null, $isActive = true)
    {
        $announcementType = strtolower(trim($type));
        
        if ($announcementType === 'info') {
            $typeName = 'info';
        } elseif ($announcementType === 'warning') {
            $typeName = 'warning';
        } elseif ($announcementType === 'success') {
            $typeName = 'success';
        } elseif ($announcementType === 'error') {
            $typeName = 'error';
        } elseif ($announcementType === 'reminder') {
            $typeName = 'reminder';
        } elseif ($announcementType === 'general') {
            $typeName = 'general';
        } else {
            $typeName = 'general';
        }

        $priorityName = null;
        if ($priority !== null) {
            $priorityLower = strtolower(trim($priority));
            if ($priorityLower === 'low' || $priorityLower === 'medium' || $priorityLower === 'high' || $priorityLower === 'urgent') {
                $priorityName = $priorityLower;
            }
        }

        return Announcement::create([
            'title' => $title,
            'content' => $content,
            'type' => $typeName,
            'priority' => $priorityName,
            'created_by' => $createdBy,
            'target_audience' => $targetAudience,
            'target_user_ids' => $targetUserIds,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
        ]);
    }
}
