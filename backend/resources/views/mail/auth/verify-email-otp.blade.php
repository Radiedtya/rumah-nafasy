<x-mail::message>
# Halo {{ $name }},

Terima kasih telah mendaftar di **{{ config('app.name', 'Rumah Natasy') }}**.

Gunakan kode di bawah ini untuk memverifikasi alamat email Anda ({{ $maskedEmail }}):

<x-mail::panel>
<div style="text-align:center;">
  <span style="display:inline-block; font-size:32px; font-weight:700; letter-spacing:12px; font-family:monospace;">{{ $otp }}</span>
</div>
</x-mail::panel>

Kode ini berlaku selama **{{ $expiresMinutes }} menit** dan hanya dapat digunakan **{{ config('emailotp.max_attempts', 5) }} kali** percobaan.

Jika Anda tidak merasa mendaftar, abaikan email ini — akun Anda tidak akan aktif tanpa verifikasi.

Terima kasih,<br>
{{ config('app.name', 'Rumah Natasy') }}
</x-mail::message>
