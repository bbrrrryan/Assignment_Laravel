<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facility_id',
        'booking_number',
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
        'special_requirements',
        'reschedule_status',
        'requested_booking_date',
        'requested_start_time',
        'requested_end_time',
        'reschedule_reason',
        'reschedule_requested_at',
        'reschedule_processed_by',
        'reschedule_processed_at',
        'reschedule_rejection_reason',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'special_requirements' => 'array',
        'requested_booking_date' => 'date',
        'requested_start_time' => 'datetime',
        'requested_end_time' => 'datetime',
        'reschedule_requested_at' => 'datetime',
        'reschedule_processed_at' => 'datetime',
    ];

    // Relationships
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

    public function statusHistory()
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function rescheduleProcessor()
    {
        return $this->belongsTo(User::class, 'reschedule_processed_by');
    }
}
