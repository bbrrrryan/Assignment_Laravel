<?php
/**
 * Author: Liew Zi Li
 * Module: Announcement Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\Announcement;

class AnnouncementFactory
{
    /**
     * Create an announcement with type string
     * 
     * @param string $type Announcement type ('info', 'warning', 'success', 'error', 'reminder', 'general')
     * @param string $title
     * @param string $content
     * @param string $targetAudience
     * @param int|null $createdBy
     * @param string|null $priority
     * @param array|null $targetUserIds
     * @param string|null $publishedAt
     * @param string|null $expiresAt
     * @param bool $isActive
     * @return Announcement
     */
    public static function makeAnnouncement($type, $title, $content, $targetAudience, $createdBy = null, $priority = null, $targetUserIds = null, $publishedAt = null, $expiresAt = null, $isActive = true)
    {
        // Normalize announcement type
        $announcementType = strtolower(trim($type));
        
        // Validate type - using simple if-else
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
            // Default to general if invalid
            $typeName = 'general';
        }

        // Validate priority - using simple if-else
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

