<?php

namespace App\Enums;

enum IdType: string
{
    case KTP = 'ktp';
    case SIM = 'sim';
    case KARTU_PELAJAR = 'kartu_pelajar';
    case KTM = 'ktm';
    case PASPOR = 'paspor';

    public function label(): string
    {
        return match ($this) {
            self::KTP => 'KTP',
            self::SIM => 'SIM',
            self::KARTU_PELAJAR => 'Kartu Pelajar',
            self::KTM => 'Kartu Mahasiswa (KTM)',
            self::PASPOR => 'Paspor',
        };
    }
}