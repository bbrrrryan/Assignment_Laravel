<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;

class BookingNotificationService
{
    /**
     * Send notification to user about booking status change
     */
    public function sendBookingNotification(Booking $booking, string $status, string $message): void
    {
        try {
            // Don't send "Booking Submitted" notification to students
            if ($status === 'pending') {
                // Only notify admins about pending bookings, not the student
                $this->notifyAdminsAboutPendingBooking($booking);
                return;
            }

            // Determine notification type based on status
            $type = $this->getNotificationType($status);
            $title = $this->getNotificationTitle($status);

            // Create detailed message
            $detailedMessage = $this->buildNotificationMessage($booking, $message);

            // Create notification
            $notification = Notification::create([
                'title' => $title,
                'message' => $detailedMessage,
                'type' => $type,
                'priority' => 'medium',
                'created_by' => auth()->id(),
                'target_audience' => 'specific',
                'target_user_ids' => [$booking->user_id],
                'is_active' => true,
            ]);

            // Send notification to user
            $notification->users()->sync([
                $booking->user_id => [
                    'is_read' => false,
                    'is_acknowledged' => false,
                ]
            ]);

            // Update scheduled_at
            $notification->update(['scheduled_at' => now()]);

        } catch (\Exception $e) {
            // Log error but don't fail the booking operation
            \Log::warning('Failed to send booking notification: ' . $e->getMessage());
        }
    }

    /**
     * Notify admins about new pending booking
     */
    private function notifyAdminsAboutPendingBooking(Booking $booking): void
    {
        try {
            $booking->load(['user', 'facility']);
            $userName = $booking->user->name ?? 'A user';
            $facilityName = $booking->facility->name ?? 'Unknown Facility';
            $bookingDate = $booking->booking_date->format('Y-m-d');
            $startTime = $booking->start_time->format('H:i');
            $endTime = $booking->end_time->format('H:i');
            
            $title = 'New Booking Request';
            $message = "{$userName} has submitted a new booking request:\n\n";
            $message .= "Facility: {$facilityName}\n";
            $message .= "Date: {$bookingDate}\n";
            $message .= "Time: {$startTime} - {$endTime}\n";
            $message .= "Booking ID: #{$booking->id}";
            
            // Get all admin user IDs
            $adminUserIds = \App\Models\User::where('status', 'active')
                ->where(function($query) {
                    $query->where('role', 'admin')
                          ->orWhere('role', 'administrator');
                })
                ->pluck('id')
                ->toArray();
            
            if (empty($adminUserIds)) {
                \Log::warning('No active admin users found to notify about booking #' . $booking->id);
                return;
            }
            
            $notification = Notification::create([
                'title' => $title,
                'message' => $message,
                'type' => 'info',
                'priority' => 'medium',
                'created_by' => $booking->user_id,
                'target_audience' => 'specific',
                'target_user_ids' => $adminUserIds,
                'is_active' => true,
                'scheduled_at' => now(),
            ]);
            
            // Sync notification to all admin users
            $syncData = [];
            foreach ($adminUserIds as $adminId) {
                $syncData[$adminId] = [
                    'is_read' => false,
                    'is_acknowledged' => false,
                ];
            }
            $notification->users()->sync($syncData);
            
            \Log::info('Booking notification sent to ' . count($adminUserIds) . ' admin(s)', [
                'notification_id' => $notification->id,
                'booking_id' => $booking->id,
                'admin_ids' => $adminUserIds
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send booking notification to admins: ' . $e->getMessage(), [
                'booking_id' => $booking->id ?? null,
                'exception' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get notification type based on status
     */
    private function getNotificationType(string $status): string
    {
        return match($status) {
            'approved' => 'success',
            'rejected' => 'error',
            'cancelled' => 'warning',
            default => 'info',
        };
    }

    /**
     * Get notification title based on status
     */
    private function getNotificationTitle(string $status): string
    {
        return match($status) {
            'approved' => 'Booking Approved',
            'rejected' => 'Booking Rejected',
            'cancelled' => 'Booking Cancelled',
            'pending' => 'Booking Submitted',
            default => 'Booking ' . ucfirst($status),
        };
    }

    /**
     * Build detailed notification message
     */
    private function buildNotificationMessage(Booking $booking, string $baseMessage): string
    {
        $facilityName = $booking->facility->name ?? 'Facility';
        $bookingDate = $booking->booking_date->format('Y-m-d');
        $startTime = $booking->start_time->format('H:i');
        $endTime = $booking->end_time->format('H:i');
        
        $message = $baseMessage . "\n\n";
        $message .= "Facility: {$facilityName}\n";
        $message .= "Date: {$bookingDate}\n";
        $message .= "Time: {$startTime} - {$endTime}\n";
        $message .= "Booking ID: {$booking->id}";
        
        return $message;
    }
}

