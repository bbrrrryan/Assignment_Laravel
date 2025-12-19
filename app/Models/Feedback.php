<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'user_id',
        'facility_id',
        'type',
        'subject',
        'message',
        'image',
        'rating',
        'status',
        'reviewed_by',
        'admin_response',
        'reviewed_at',
        'is_blocked',
        'block_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'is_blocked' => 'boolean',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
