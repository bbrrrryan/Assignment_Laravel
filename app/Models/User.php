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
        'otp_code',
        'otp_expires_at',
        'personal_id',
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
    ];

    // Relationships
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'user_notification')
                    ->withPivot('is_read', 'read_at', 'is_acknowledged', 'acknowledged_at', 'is_starred', 'starred_at')
                    ->withTimestamps();
    }

    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'user_announcement')
                    ->withPivot('is_read', 'read_at', 'is_starred', 'starred_at')
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
     * Generate a unique student personal ID
     * Format: YYWMR##### (e.g., 25WMR00001)
     * YY: 2-digit year (e.g., 25 for 2025)
     * WMR: Fixed prefix
     * #####: 5-digit sequential number starting from 00001
     * 
     * @return string
     */
    public static function generateStudentId()
    {
        $year = date('y');
        $prefix = $year . 'WMR';
        
        $lastStudent = self::whereNotNull('personal_id')
            ->where('personal_id', 'like', $prefix . '%')
            ->orderBy('personal_id', 'desc')
            ->first();
        
        if ($lastStudent && $lastStudent->personal_id) {
            $lastNumber = intval(substr($lastStudent->personal_id, 5));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique staff personal ID
     * Format: p#### (e.g., p0001, p0002, p0003...)
     * p: Fixed prefix
     * ####: 4-digit sequential number starting from 0001
     * 
     * @return string
     */
    public static function generateStaffId()
    {
        $prefix = 'p';
        
        $lastStaff = self::whereNotNull('personal_id')
            ->where('personal_id', 'like', $prefix . '%')
            ->orderBy('personal_id', 'desc')
            ->first();
        
        if ($lastStaff && $lastStaff->personal_id) {
            // Extract number from personal_id (e.g., "p0001" -> 1)
            $lastNumber = intval(substr($lastStaff->personal_id, 1));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

}