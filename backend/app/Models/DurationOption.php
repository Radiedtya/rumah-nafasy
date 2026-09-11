<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DurationOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'minutes',
        'multiplier',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function orders()
    {
        return $this->hasMany(Order::class, 'duration_id');
    }
}