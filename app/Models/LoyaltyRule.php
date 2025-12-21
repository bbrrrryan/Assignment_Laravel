<?php
/**
 * Author: Boo Kai Jie
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_type',
        'name',
        'description',
        'points',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];
}
