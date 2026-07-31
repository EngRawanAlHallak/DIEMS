<?php

namespace App\Actions\Auth;

use App\Services\OtpService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class VerifyOtpAction
{
    public function __construct(private readonly OtpService $otpService) {}

    public function execute(string $identifier, string $otp): string
    {
        $result = $this->otpService->verify($identifier, $otp);

        if (! $result['valid']) {
            Log::warning('[VerifyOtp] Failed', [
                'identifier'  => $identifier,
                'reason' => $result['message'],
            ]);

            throw ValidationException::withMessages([
                'otp' => [$result['message']],
            ]);
        }

        Log::info('[VerifyOtp] Success', ['identifier' => $identifier]);

        return $result['reset_token'];
    }
}
