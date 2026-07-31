<?php
namespace App\Actions\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function __construct(private readonly OtpService $otpService) {}

    public function execute(string $identifier, string $resetToken, string $newPassword): void
    {
        if (! $this->otpService->verifyResetToken($identifier, $resetToken)) {
            Log::warning('[ResetPassword] Invalid token', ['identifier' => $identifier]);

            throw ValidationException::withMessages([
                'reset_token' => ['Invalid or expired token. Please request a new OTP.'],
            ]);
        }

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phonenumber';
        $account = User::where($field, $identifier)->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'email' => ['Account not found.'],
            ]);
        }

        $account->update([
            'password'        => Hash::make($newPassword),
            'failed_attempts' => 0,
            'locked_until'    => null,
            'last_failed_at'  => null,
        ]);

        // Invalidate token + revoke all active sessions
        $this->otpService->invalidateResetToken($identifier);
        $account->tokens()->delete();

        Log::info('[ResetPassword] Success', [
            'identifier'      => $identifier,
            'account_id' => $account->id,
        ]);
    }
}
