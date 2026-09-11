<?php

namespace App\Enums;

enum BookingStatus: string
{
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Dikonfirmasi',
            self::IN_PROGRESS => 'Sedang Berlangsung',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
            self::RESCHEDULED => 'Dijadwalkan Ulang',
        };
    }
}