<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class VerifyEmailOtp extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /** Path absolut logo (null bila file tidak ada — email tetap terkirim). */
    public ?string $logoPath = null;

    /**
     * @param  string  $maskedEmail  Email ter-masked (r***@domain) — aman tampil di body
     */
    public function __construct(
        public string $name,
        public string $otp,
        public string $maskedEmail,
        public int $expiresMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Kode Verifikasi Email — :app', ['app' => config('app.name', 'Rumah Natasy')]),
        );
    }

    /**
     * Siapkan logo ter-embed: tampil langsung di semua klien email tanpa
     * "klik untuk menampilkan gambar". (Logo lewat URL publik sering
     * diblokir Gmail/Outlook — inilah penyebab logo tidak muncul.)
     * Embed-nya sendiri dilakukan di view via $message->embed($logoPath)
     * — method bawaan Laravel untuk inline attachment.
     */
    public function build(): static
    {
        $logo = public_path('logo/rumah-natasy-256.png');

        if (is_file($logo)) {
            $this->logoPath = $logo;
        }

        return $this;
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.auth.verify-email-otp',
        );
    }

    /**
     * Header spam-ramah untuk menurunkan peluang masuk folder promo.
     *
     * Wajib objek Headers (Laravel 13+): method headers() yang mengembalikan
     * array membuat queue job gagal saat hydratasi header.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'otp-'.Str::random(16),
            ],
        );
    }
}
