<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'slot_date',
        'start_time',
        'end_time',
        'duration_hours',
    ];

    protected $casts = [
        'slot_date' => 'date',
        // start_time and end_time are stored as time (HH:mm:ss) in database
        // Keep them as strings to avoid datetime conversion issues
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
