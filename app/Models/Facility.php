<?php
/**
 * Author:Ng Jhun Hou
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'location',
        'capacity',
        'enable_multi_attendees',
        'max_attendees',
        'available_day',
        'available_time',
        'equipment',
        'rules',
        'status',
        'image_url',
        'max_booking_hours',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'available_day' => 'array',
        'available_time' => 'array',
        'equipment' => 'array',
        'enable_multi_attendees' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    // Relationships
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    // Relationships for created_by and updated_by
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
