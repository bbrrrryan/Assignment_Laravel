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
        'available_times',
        'equipment',
        'rules',
        'status',
        'image_url',
        'requires_approval',
        'booking_advance_days',
        'max_booking_hours',
    ];

    protected $casts = [
        'available_times' => 'array',
        'equipment' => 'array',
        'requires_approval' => 'boolean',
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
