<?php

return [
    'api_key' => env('FONNTE_API_KEY'),
    'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com'),
    'is_configured' => !empty(env('FONNTE_API_KEY')),
    'sender_name' => 'Rumah Natasy',
];