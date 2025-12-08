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
        'role_id',
        'role', // Keep for backward compatibility
        'status',
        'phone_number',
        'address',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

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

    public function hasPermission($permission)
    {
        if (!$this->role) {
            return false;
        }
        return $this->role->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        // Check if role is a relationship object
        if ($this->relationLoaded('role') && $this->role instanceof Role) {
            return $this->role->name === 'admin' || 
                   strtolower($this->role->name) === 'administrator';
        }
        
        // Fallback: check role_id or role string attribute
        if ($this->role_id === 1) {
            return true;
        }
        
        // Check if role is a string (backward compatibility)
        if (is_string($this->attributes['role'] ?? null)) {
            $roleName = strtolower($this->attributes['role']);
            return $roleName === 'admin' || $roleName === 'administrator';
        }
        
        return false;
    }

    /**
     * Check if user is staff
     */
    public function isStaff()
    {
        // Check if role is a relationship object
        if ($this->relationLoaded('role') && $this->role instanceof Role) {
            return strtolower($this->role->name) === 'staff';
        }
        
        // Check if role is a string (backward compatibility)
        if (is_string($this->attributes['role'] ?? null)) {
            return strtolower($this->attributes['role']) === 'staff';
        }
        
        return false;
    }

    /**
     * Check if user is student
     */
    public function isStudent()
    {
        // Check if role is a relationship object
        if ($this->relationLoaded('role') && $this->role instanceof Role) {
            return strtolower($this->role->name) === 'student';
        }
        
        // Check if role is a string (backward compatibility)
        if (is_string($this->attributes['role'] ?? null)) {
            return strtolower($this->attributes['role']) === 'student';
        }
        
        return false;
    }
}