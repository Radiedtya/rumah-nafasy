<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Send WhatsApp message to a phone number.
     * Mock mode if API key not configured.
     */
    public function send(string $phone, string $message): array
    {
        // Mock mode for development
        if (!config('fonnte.is_configured')) {
            Log::info("[MOCK WA] To: {$phone}", ['message' => $message]);
            return ['success' => true, 'is_mock' => true, 'message' => $message];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => config('fonnte.api_key'),
            ])->post(config('fonnte.api_url') . '/send', [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? false)) {
                Log::info("WA sent to {$phone}", ['response' => $data]);
                return ['success' => true, 'is_mock' => false, 'data' => $data];
            }

            Log::error("Fonnte send failed", ['phone' => $phone, 'response' => $data]);
            return ['success' => false, 'is_mock' => false, 'error' => $data];

        } catch (\Exception $e) {
            Log::error("Fonnte exception: " . $e->getMessage(), ['phone' => $phone]);
            return ['success' => false, 'is_mock' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Dispatch WA notification as queue job (async).
     */
    public function notifyUser(User $user, string $message): void
    {
        $phone = $this->formatPhone($user->phone);
        SendWhatsAppNotification::dispatch($phone, $message);
    }

    /**
     * Send immediately (sync, for scheduler/cron).
     */
    public function sendNow(User $user, string $message): array
    {
        $phone = $this->formatPhone($user->phone);
        return $this->send($phone, $message);
    }

    /**
     * Format Indonesian phone number for Fonnte.
     * 08xxx → 628xxx
     */
    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
}