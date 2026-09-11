<?php

namespace App\Enums;

enum UserRole: string
{
    case PASIEN = 'pasien';
    case PSIKOLOG = 'psikolog';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::PASIEN => 'Pasien',
            self::PSIKOLOG => 'Psikolog',
            self::ADMIN => 'Admin',
        };
    }
}