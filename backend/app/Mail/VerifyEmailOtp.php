<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailOtp extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  string  $maskedEmail  Email termasked (r***@domain) — aman tampil di body
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

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.verify-email-otp',
        );
    }
}
