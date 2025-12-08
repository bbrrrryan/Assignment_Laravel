<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'created_by',
        'target_audience',
        'target_user_ids',
        'scheduled_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_notification')
                    ->withPivot('is_read', 'read_at', 'is_acknowledged', 'acknowledged_at')
                    ->withTimestamps();
    }
}
