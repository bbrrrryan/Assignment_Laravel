<?php

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
        'requires_approval',
        'max_booking_hours',
    ];

    protected $casts = [
        'available_day' => 'array',
        'available_time' => 'array',
        'equipment' => 'array',
        'requires_approval' => 'boolean',
        'enable_multi_attendees' => 'boolean',
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
}
