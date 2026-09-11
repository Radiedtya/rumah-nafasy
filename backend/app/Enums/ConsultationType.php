<?php

namespace App\Enums;

enum ConsultationType: string
{
    case VIDEO = 'video';
    case CHAT = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::VIDEO => 'Video Call',
            self::CHAT => 'Chat',
        };
    }
}