<?php

// app/Services/OtpService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const OTP_TTL       = 600;  // 10 minutes
    private const TOKEN_TTL     = 300;  // 5 minutes
    private const MAX_ATTEMPTS  = 5;
    private const OTP_COOLDOWN  = 60;   // 1 minutes

    // ── Keys ─────────────────────────────────────────────────────────────

    private function otpKey(string $email): string
    {
        return 'otp:' . md5($email); // يشفر الايميل بطول ثابت 32حرف
    }

    private function cooldownKey(string $email): string
    {
        return 'otp_cooldown:' . md5($email); //نمنع ال spamming
    }

    private function resetTokenKey(string $email): string
    {
        return 'reset_token:' . md5($email);
    }

    /**
     * Generate and store OTP in Redis.
     * Returns null if still in cooldown.
     */
    public function generate(string $identifier): ?string
    {
        $email = $this->userEmail($identifier);

        // Cooldown check — prevent spam
        if (Cache::has($this->cooldownKey($email))) {
            return null;
        }

        $otp = (string) random_int(100000, 999999);
        $ttl = self::OTP_TTL;
        $cooldown = self::OTP_COOLDOWN;

        Cache::put($this->otpKey($email), [
            'hash'     => hash('sha256', $otp),
            'attempts' => 0,
        ], $ttl);

        // Cooldown window — separate key
        Cache::put($this->cooldownKey($email), true, $cooldown);

        return $otp;
    }

    // ── Verify (Atomic) ───────────────────────────────────────────────────

    /**
     * Atomically verify OTP.
     * Returns: ['valid' => bool, 'message' => string, 'reset_token' => string|null]
     */
    public function verify(string $identifier, string $otp): array
    {
        $email = $this->userEmail($identifier);

        $otpKey     = $this->otpKey($email);
        $maxAttempts = self::MAX_ATTEMPTS;

        // Atomic lock — prevent two requests verifying simultaneously
        $lock = Cache::lock("otp_verify:{$email}", 5);

        if (! $lock->get()) {
            return [
                'valid'       => false,
                'message'     => 'Too many simultaneous requests. Try again.',
                'reset_token' => null,
            ];
        }

        try {
            $data = Cache::get($otpKey);

            if (! $data) {
                return [
                    'valid'       => false,
                    'message'     => 'OTP expired or not found. Please request a new one.',
                    'reset_token' => null,
                ];
            }

            if ($data['attempts'] >= $maxAttempts) {
                Cache::forget($otpKey);
                return [
                    'valid'       => false,
                    'message'     => 'Too many wrong attempts. Please request a new OTP.',
                    'reset_token' => null,
                ];
            }

            if (! hash_equals($data['hash'], hash('sha256', $otp))) {
                // Increment attempts atomically inside the lock
                $data['attempts']++;
                $ttl = self::OTP_TTL;
                Cache::put($otpKey, $data, $ttl);

                $remaining = $maxAttempts - $data['attempts'];
                return [
                    'valid'       => false,
                    'message'     => "Incorrect OTP. {$remaining} attempt(s) remaining.",
                    'reset_token' => null,
                ];
            }

            // ── Valid OTP ─────────────────────────────────────────────
            Cache::forget($otpKey);

            $resetToken    = bin2hex(random_bytes(32));
            $resetTokenTtl = self::TOKEN_TTL;

            Cache::put(
                $this->resetTokenKey($email),
                hash('sha256', $resetToken),
                $resetTokenTtl
            );

            return [
                'valid'       => true,
                'message'     => 'OTP verified successfully.',
                'reset_token' => $resetToken,
            ];

        } finally {
            $lock->release();
        }
    }

    // ── Reset Token ───────────────────────────────────────────────────────

    public function verifyResetToken(string $identifier, string $token): bool
    {
        $email = $this->userEmail($identifier);
        $stored = Cache::get($this->resetTokenKey($email));
        return $stored && hash_equals($stored, hash('sha256', $token));
    }

    public function invalidateResetToken(string $identifier): void
    {
        $email = $this->userEmail($identifier);
        Cache::forget($this->resetTokenKey($email));
    }

    private function userEmail(string $identifier)
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phonenumber';
        $user = User::where($field, $identifier)->first();
        if ($user === null)
            throw ValidationException::withMessages([
                'identifier' => ['Account not found.'],
            ]);

        return $user->email;
    }
}
