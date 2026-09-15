<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;

class JitsiService
{
    /**
     * Generate JWT token for Jitsi meeting.
     * Returns null in dev mode (meet.jit.si doesn't require token).
     */
    public function generateToken(Booking $booking, User $user): ?string
    {
        // Dev mode: meet.jit.si doesn't need JWT
        if (!config('jitsi.is_configured')) {
            return null;
        }

        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]);

        $payload = json_encode([
            'iss' => config('jitsi.app_id'),
            'aud' => config('jitsi.app_id'),
            'sub' => config('jitsi.domain'),
            'room' => $booking->room_id,
            'exp' => now()->addHours(2)->timestamp,
            'context' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                        ? asset('storage/' . $user->avatar)
                        : null,
                ],
                'features' => [
                    'livestreaming' => false,
                    'recording' => false,
                    'transcription' => false,
                    'outbound-call' => false,
                ],
            ],
            'moderator' => $user->isPsikolog(),
        ]);

        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            config('jitsi.app_secret'),
            true
        );

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Get meeting info for a booking.
     */
    public function getMeetingInfo(Booking $booking, User $user): array
    {
        $token = $this->generateToken($booking, $user);

        return [
            'domain' => config('jitsi.domain'),
            'room_id' => $booking->room_id,
            'token' => $token,
            'is_moderator' => $user->isPsikolog(),
            'is_mock' => !config('jitsi.is_configured'),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : null,
            ],
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}