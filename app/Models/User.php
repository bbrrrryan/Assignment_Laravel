<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $status
 * @property string|null $phone_number
 * @property string|null $address
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserActivityLog> $activityLogs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Notification> $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LoyaltyPoint> $loyaltyPoints
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Certificate> $certificates
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Reward> $rewards
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Feedback> $feedbacks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Booking> $bookings
 * 
 * @method \Illuminate\Database\Eloquent\Relations\HasMany activityLogs()
 * @method bool update(array $attributes = [])
 * @method bool save(array $options = [])
 * @method static self fresh(array|string $with = [])
 * @method array getMergedSettings()
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
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'otp_expires_at' => 'datetime',
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
     * Get user setting value
     */
    public function getSetting($key, $default = null)
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set user setting value
     */
    public function setSetting($key, $value)
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        return $this;
    }

    /**
     * Get default settings
     */
    public static function getDefaultSettings()
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
     * Get merged settings with defaults
     */
    public function getMergedSettings()
    {
        $defaults = self::getDefaultSettings();
        $userSettings = $this->settings ?? [];
        
        return array_merge($defaults, $userSettings);
    }
}