<?php

return [

    /*
    |----------------------------------------------------------------------
    | OTP Verifikasi Email (Resend)
    |----------------------------------------------------------------------
    | Kode 6 digit dikirim via Resend. Kode disimpan ter-hash, punya TTL,
    | cooldown antar kirim, dan kuota per-jam — anti spam & brute-force.
    |
    */

    'length' => 6,                 // panjang kode OTP
    'ttl_minutes' => 10,           // masa hidup OTP
    'max_attempts' => 5,           // batas tebakan per OTP
    'resend_cooldown_seconds' => 60,   // jeda minimal antar kirim ke 1 email
    'max_sends_per_hour' => 3,         // kuota kirim per email per jam (hard anti-spam)

    // Masa berlaku pendaftaran yang menunggu verifikasi OTP. Setelah lewat,
    // kandidat akun (pending_registrations) kedaluwarsa & bisa dibersihkan.
    'pending_ttl_minutes' => 60,

];
