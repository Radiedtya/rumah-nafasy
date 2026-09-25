<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestResendEmail extends Command
{
    /**
     * ── Tes pipeline email (Resend) ──────────────────────────────────────
     *
     * Memastikan RESEND_API_KEY valid, MAIL_MAILER=resend aktif, dan
     * domain pengirim (MAIL_FROM_ADDRESS) terverifikasi — SEBELUM dicoba
     * lewat alur register sungguhan.
     *
     * Tanpa --queue: kirim SINKRON (langsung terlihat sukses/gagal —
     * error 403 dari Resend langsung muncul di sini).
     * Dengan --queue: ikuti jalur persis seperti OTP asli (perlu worker).
     *
     * Pakai:
     *   php artisan resend:test email@anda.com
     *   php artisan resend:test email@anda.com --queue
     */
    protected $signature = 'resend:test {email : Alamat penerima} {--queue : Kirim lewat queue seperti OTP asli (perlu worker)}';

    protected $description = 'Kirim email tes via transport Resend untuk memverifikasi konfigurasi';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Alamat email tidak valid: {$email}");

            return self::FAILURE;
        }

        // ── Pra-cek konfigurasi ──────────────────────────────────────────
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $resendKey = (string) config('services.resend.key');

        $this->info("Mail default   : {$mailer}");
        $this->info("From           : {$fromName} <{$from}>");
        $this->info('RESEND_API_KEY : '.($resendKey !== '' ? 'terisi ('.strlen($resendKey).' char)' : 'KOSONG ⚠'));

        if ($resendKey === '') {
            $this->error('RESEND_API_KEY belum diisi di backend/.env');

            return self::FAILURE;
        }

        if ($mailer !== 'resend') {
            $this->warn("MAIL_MAILER masih '{$mailer}' — OTP TIDAK akan lewat Resend!");
        }

        // Resend MENOLAK (403) pengiriman dari domain yang belum terverifikasi.
        if (! filter_var($from, FILTER_VALIDATE_EMAIL) || str_ends_with($from, '@example.com')) {
            $this->warn("From '{$from}' TIDAK berada di domain terverifikasi Resend → API akan menjawab 403.");
            $this->line("Perbaiki di .env:  MAIL_FROM_ADDRESS=no-reply@rumah-nafsy.gg7.dev  lalu  php artisan config:clear");
        }

        $code = (string) random_int(100000, 999999);

        // ── Jalur queue (persis alur OTP asli) ───────────────────────────
        if ($this->option('queue')) {
            Mail::to($email)->queue(new \App\Mail\VerifyEmailOtp('Rumah Nafasy', $code, $email, 10));
            $this->info('Mailable di-push ke queue — pastikan worker jalan: php artisan queue:work');
            $this->line('Cek hasil kirim: dashboard Resend → Logs (200 = sukses, 403 = from/domain ditolak).');

            return self::SUCCESS;
        }

        // ── Jalur sinkron: error Resend LANGSUNG terlihat ────────────────
        try {
            Mail::raw(
                "Tes pipeline Resend — Rumah Nafasy.\n\nKode tes Anda: {$code}\n\n(Baik kalau email ini sampai — konfigurasi Resend sudah benar.)",
                function ($message) use ($email, $fromName) {
                    $message->to($email)->subject("Tes Resend — Rumah Nafasy [{$fromName}]");
                }
            );
        } catch (\Throwable $e) {
            $this->error('GAGAL: '.$e->getMessage());
            $this->line('— Penyebab paling umum: MAIL_FROM_ADDRESS bukan domain terverifikasi, atau RESEND_API_KEY salah.');

            return self::FAILURE;
        }

        $this->info("TERKIRIM ✓  (kode tes: {$code})");
        $this->line('Periksa inbox + dashboard Resend → Logs (harus 200, bukan 403).');

        return self::SUCCESS;
    }
}
