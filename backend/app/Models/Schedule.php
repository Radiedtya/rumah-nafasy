<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'psikolog_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    // Relationships

    public function psikolog()
    {
        return $this->belongsTo(User::class, 'psikolog_id');
    }

    // Helpers

    public function getDayName(): string
    {
        $days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $days[$this->day_of_week] ?? 'Unknown';
    }

    // Accessors

    public function getStartTimeAttribute($value): string
    {
        return $value ? substr($value, 0, 5) : '';
    }

    public function getEndTimeAttribute($value): string
    {
        return $value ? substr($value, 0, 5) : '';
    }
}