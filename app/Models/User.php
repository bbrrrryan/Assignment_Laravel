<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone_number',
        'address',
        'last_login_at',
        'settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
    ];

    // Relationships
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'user_notification')
                    ->withPivot('is_read', 'read_at', 'is_acknowledged', 'acknowledged_at')
                    ->withTimestamps();
    }

    public function loyaltyPoints()
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function rewards()
    {
        return $this->belongsToMany(Reward::class, 'user_reward')
                    ->withPivot('points_used', 'status', 'approved_by', 'redeemed_at')
                    ->withTimestamps();
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Helper methods
    public function getTotalPointsAttribute()
    {
        return $this->loyaltyPoints()->sum('points');
    }

    /**
     * Check if user is admin - using simple if-else
     */
    public function isAdmin()
    {
        $role = strtolower($this->role ?? '');
        
        if ($role === 'admin' || $role === 'administrator') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if user is staff - using simple if-else
     */
    public function isStaff()
    {
        $role = strtolower($this->role ?? '');
        
        if ($role === 'staff') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if user is student - using simple if-else
     */
    public function isStudent()
    {
        $role = strtolower($this->role ?? '');
        
        if ($role === 'student') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get default user settings
     */
    public function getDefaultSettings()
    {
        return [
            'notifications' => [
                'email' => true,
                'system' => true,
                'booking_reminders' => true,
                'facility_maintenance' => true,
                'loyalty_rewards' => true,
            ],
        ];
    }

    /**
     * Get a specific setting value
     */
    public function getSetting($key, $default = null)
    {
        $settings = $this->settings ?? [];
        $keys = explode('.', $key);
        $value = $settings;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set a specific setting value
     */
    public function setSetting($key, $value)
    {
        $settings = $this->settings ?? [];
        $keys = explode('.', $key);
        $current = &$settings;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
        $this->settings = $settings;
        return $this;
    }

    /**
     * Get merged settings (user settings merged with defaults)
     */
    public function getMergedSettings()
    {
        $defaults = $this->getDefaultSettings();
        $userSettings = $this->settings ?? [];
        
        return array_merge_recursive($defaults, $userSettings);
    }
}