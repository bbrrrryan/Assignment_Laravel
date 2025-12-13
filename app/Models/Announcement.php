<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'created_by',
        'target_audience',
        'target_user_ids',
        'published_at',
        'expires_at',
        'is_active',
        'is_pinned',
        'views_count',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
        'views_count' => 'integer',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_announcement')
                    ->withPivot('is_read', 'read_at')
                    ->withTimestamps();
    }
}
