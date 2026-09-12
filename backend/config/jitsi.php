<?php

return [
    'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
    'app_id' => env('JITSI_APP_ID', 'rumah-natasy'),
    'app_secret' => env('JITSI_APP_SECRET', ''),
    'is_configured' => !empty(env('JITSI_APP_SECRET')),
    'is_production' => env('JITSI_DOMAIN') !== 'meet.jit.si',
];