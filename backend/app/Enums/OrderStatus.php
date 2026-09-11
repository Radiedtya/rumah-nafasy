<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar',
            self::SCHEDULED => 'Terjadwal',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
            self::REFUNDED => 'Dana Dikembalikan',
            self::EXPIRED => 'Kedaluwarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'amber',
            self::PAID => 'blue',
            self::SCHEDULED => 'purple',
            self::COMPLETED => 'green',
            self::CANCELLED => 'rose',
            self::REFUNDED => 'orange',
            self::EXPIRED => 'gray',
        };
    }
}