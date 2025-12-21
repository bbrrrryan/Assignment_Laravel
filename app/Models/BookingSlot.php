<?php
/**
 * Author: Low Kim Hong
 */
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

    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
