<?php
/**
 * Author: Low Kim Hong
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facility_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration_hours',
        'purpose',
        'expected_attendees',
        'status',
        'approved_by',
        'rejection_reason',
        'approved_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function slots()
    {
        return $this->hasMany(BookingSlot::class);
    }
}
