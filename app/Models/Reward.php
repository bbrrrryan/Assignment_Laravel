<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_required',
        'reward_type',
        'image_url',
        'stock_quantity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_reward')
                    ->withPivot('points_used', 'status', 'approved_by', 'redeemed_at')
                    ->withTimestamps();
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
