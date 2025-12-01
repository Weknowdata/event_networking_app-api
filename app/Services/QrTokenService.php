<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QrTokenService
{
    private string $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?? (string) config('qr.signing_secret', '');

        if ($this->secret === '') {
            throw new RuntimeException('QR signing secret is not configured.');
        }
    }

    public function mintToken(int $agendaSlotId, int $ttlSeconds = 900): string
    {
        $expiresAt = CarbonImmutable::now()->addSeconds($ttlSeconds)->getTimestampMs();

        return $this->signPayload([
            'agenda_slot_id' => $agendaSlotId,
            'exp' => $expiresAt,
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateForAgendaSlot(string $token, int $agendaSlotId): array
    {
        $payload = $this->decodeAndVerify($token);

        $payloadSlotId = $this->extractSlotId($payload);
        if ($payloadSlotId !== $agendaSlotId) {
            throw ValidationException::withMessages([
                'qr_token' => ['QR token does not match this session.'],
            ]);
        }

        $expiresAtMs = $this->extractExpiration($payload);
        $expiresAt = CarbonImmutable::createFromTimestampMs($expiresAtMs);

        if ($expiresAt->isPast()) {
            throw ValidationException::withMessages([
                'qr_token' => ['QR code has expired. Please refresh and try again.'],
            ]);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function signPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to encode QR payload.');
        }

        $payloadSegment = $this->base64UrlEncode($json);
        $signature = $this->signatureFor($payloadSegment);

        return $payloadSegment.'.'.$this->base64UrlEncode($signature);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAndVerify(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 2) {
            throw ValidationException::withMessages([
                'qr_token' => ['Invalid QR token format.'],
            ]);
        }

        [$payloadSegment, $signatureSegment] = $segments;
        $payloadJson = $this->base64UrlDecode($payloadSegment);
        $signature = $this->base64UrlDecode($signatureSegment);

        if ($payloadJson === null || $signature === null) {
            throw ValidationException::withMessages([
                'qr_token' => ['Invalid QR token encoding.'],
            ]);
        }

        $expectedSignature = $this->signatureFor($payloadSegment);

        if (! hash_equals($expectedSignature, $signature)) {
            throw ValidationException::withMessages([
                'qr_token' => ['QR token signature is invalid.'],
            ]);
        }

        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'qr_token' => ['Invalid QR token payload.'],
            ]);
        }

        return $payload;
    }

    private function signatureFor(string $payloadSegment): string
    {
        return hash_hmac('sha256', $payloadSegment, $this->secret, true);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;

        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractSlotId(array $payload): int
    {
        $slotId = $payload['agenda_slot_id'] ?? null;

        if (! is_numeric($slotId)) {
            throw ValidationException::withMessages([
                'qr_token' => ['QR token is missing a session id.'],
            ]);
        }

        return (int) $slotId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractExpiration(array $payload): int
    {
        $expiresAt = $payload['exp'] ?? null;

        if (! is_numeric($expiresAt)) {
            throw ValidationException::withMessages([
                'qr_token' => ['QR token is missing an expiration.'],
            ]);
        }

        return (int) $expiresAt;
    }
}
