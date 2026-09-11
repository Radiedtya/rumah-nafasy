<?php

namespace App\Enums;

enum PsikologStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::SUSPENDED => 'Ditangguhkan',
            self::REJECTED => 'Ditolak',
        };
    }
}