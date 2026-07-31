<?php

namespace App\Actions\Auth;

use App\Jobs\Auth\SendOtpEmailJob;
use App\Jobs\Auth\SendOtpSmsJob;
use App\Models\User;
use App\Models\Company;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ForgotPasswordAction
{
    use ApiResponse;
    public function __construct(private readonly OtpService $otpService) {}
    private const OTP_TTL       = 600;  // 10 minutes

    public function execute(string $identifier): void
    {
        $account = $this->findAccount($identifier);

        // Always return — never reveal if identifier exists
        if ($account == null) {
            Log::warning('[ForgotPassword] Identifier not found', ['identifier' => $identifier]);
            throw ValidationException::withMessages([
                'identifier' => ['Invalid credentials.'],
            ]);
        }

        $otp = $this->otpService->generate($account->email);

        // Still in cooldown — silently return (response stays 200)
        if (! $otp) {
            Log::info('[ForgotPassword] Cooldown active', ['email' => $account->email]);
            throw ValidationException::withMessages([
                'identifier' => ['Please wait a moment before requesting a new code.'],
            ]);
        }

        $ttlSeconds = self::OTP_TTL;
        $ttlMinutes = (int) ceil($ttlSeconds / 60);

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            SendOtpEmailJob::dispatch($identifier, $account->name, $otp, $ttlMinutes)
                ->onQueue('default');

            Log::info('[ForgotPassword] OTP Email dispatched', ['email' => $identifier]);

        } elseif (preg_match('/^\+963\d{9}$/', $identifier)) { // '/^09\d{8}$/'  //'/^\+963\d{9}$/'
                SendOtpSmsJob::dispatch($identifier, $otp, $ttlMinutes)
                    ->onQueue('default');

                Log::info('[ForgotPassword] OTP SMS dispatched', ['phone' => $identifier]);
        }

        Log::info('[ForgotPassword] OTP dispatched', ['email' => $identifier]);
    }

    private function findAccount(string $identifier)
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phonenumber';
        return User::where($field, $identifier)->first();
    }
}
