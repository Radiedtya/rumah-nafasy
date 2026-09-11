<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'psikolog_id',
        'content',
    ];

    // ENCRYPTED — penting buat privacy pasien (UU PDP)
    protected function casts(): array
    {
        return [
            'content' => 'encrypted',
        ];
    }

    // Relationships

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function psikolog()
    {
        return $this->belongsTo(User::class, 'psikolog_id');
    }
}