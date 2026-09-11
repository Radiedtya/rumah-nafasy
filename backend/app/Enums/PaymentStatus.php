<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::SUCCESS => 'Berhasil',
            self::FAILED => 'Gagal',
            self::REFUNDED => 'Dikembalikan',
        };
    }
}