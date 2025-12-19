<?php
/**
 * Author: Liew Zi Li
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

    public function getTotalPointsAttribute()
    {
        return $this->loyaltyPoints()->sum('points');
    }

    public function isAdmin()
    {
        $role = strtolower($this->role ?? '');
        
        if ($role === 'admin' || $role === 'administrator') {
            return true;
        } else {
            return false;
        }
    }

    public function isStaff()
    {
        $role = strtolower($this->role ?? '');
        
        if ($role === 'staff') {
            return true;
        } else {
            return false;
        }
    }

    public function isStudent()
    {
        $role = strtolower($this->role ?? '');
        
        if ($role === 'student') {
            return true;
        } else {
            return false;
        }
    }

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

    public static function generateStaffId()
    {
        $prefix = 'p';
        
        $lastStaff = self::whereNotNull('personal_id')
            ->where('personal_id', 'like', $prefix . '%')
            ->orderBy('personal_id', 'desc')
            ->first();
        
        if ($lastStaff && $lastStaff->personal_id) {
            $lastNumber = intval(substr($lastStaff->personal_id, 1));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

}