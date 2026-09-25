<?php

namespace App\Http\Requests\Auth;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // email:rfc,dns — DNS check HANYA aktif di production (blokir
            // domain tanpa MX). Di local/testing/staging cukup email:rfc —
            // lebih deterministik: tidak bergantung pada jaringan/DNS
            // eksternal maupun kondisi config cache (APP_ENV ter-bake).
            'email' => [
                'required',
                'string',
                app()->environment('production') ? 'email:rfc,dns' : 'email:rfc',
                'max:255',
                // Terdaftar di users → blokir. Email yang sedang MENUNGGU
                // OTP (pending_registrations) TIDAK diblokir di sini: register
                // ulang menimpa kandidat lama (handle baru) — proteksi spam
                // tetap dari rate limit + kuota OTP per email.
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],

            // Kebijakan password kuat — sinkron dengan validator frontend:
            // minimal 8 karakter, wajib huruf besar, huruf kecil, dan angka.
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'confirmed',
                'regex:/[A-Z]/',   // ada huruf kapital
                'regex:/[a-z]/',   // ada huruf kecil
                'regex:/[0-9]/',   // ada angka
            ],

            // Anti-bot (Cloudflare Turnstile) — diverifikasi server-side.
            // Wajib bila secret dikonfigurasi; dilewati di testing.
            'turnstile_token' => [
                Rule::requiredIf($this->turnstileRequired()),
                'nullable',
                'string',
                $this->validateTurnstile(...),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor HP wajib diisi',
            'phone.unique' => 'Nomor HP sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'password.regex' => 'Password harus mengandung huruf kapital, huruf kecil, dan angka',
            'turnstile_token.required' => 'Verifikasi keamanan (Cloudflare) wajib diselesaikan',
            'turnstile_token' => 'Verifikasi keamanan gagal — coba lagi',
        ];
    }

    private function turnstileRequired(): bool
    {
        return app()->environment('production')
            && (bool) config('services.turnstile.secret');
    }

    /**
     * Verifikasi token Turnstile ke sitverify Cloudflare (server-side).
     * Penyerang tanpa browser asli tidak bisa memalsukan token ini.
     */
    private function validateTurnstile(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = (string) config('services.turnstile.secret');

        // Belum dikonfigurasi (local/testing) → jangan blokir developer.
        if ($secret === '') {
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->retry(2, 200)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => (string) $value,
                    'remoteip' => $this->ip(),
                ]);

            if (! ($response->json('success') === true)) {
                $fail(__('Verifikasi keamanan gagal — coba lagi'));
            }
        } catch (\Throwable $e) {
            // Cloudflare tidak terjangkau → gagal-tertutup (fail-closed)
            Log::warning('Turnstile siteverify unreachable', ['error' => $e->getMessage()]);
            $fail(__('Verifikasi keamanan tidak dapat diproses — coba lagi'));
        }
    }
}
