<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->otpService = new OtpService();
    }

    public function test_generate_returns_six_digit_otp(): void
    {
        $user = User::factory()->create();

        $otp = $this->otpService->generate($user->email);

        $this->assertNotNull($otp);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    public function test_generate_returns_null_during_cooldown(): void
    {
        $user = User::factory()->create();

        $first = $this->otpService->generate($user->email);
        $second = $this->otpService->generate($user->email);

        $this->assertNotNull($first);
        $this->assertNull($second);
    }

    public function test_verify_valid_otp_returns_reset_token(): void
    {
        $user = User::factory()->create();
        $otp = $this->otpService->generate($user->email);

        $result = $this->otpService->verify($user->email, $otp);

        $this->assertTrue($result['valid']);
        $this->assertNotNull($result['reset_token']);
        $this->assertSame('OTP verified successfully.', $result['message']);
    }

    public function test_verify_invalid_otp_increments_attempts(): void
    {
        $user = User::factory()->create();
        $this->otpService->generate($user->email);

        $result = $this->otpService->verify($user->email, '000000');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Incorrect OTP', $result['message']);
    }

    public function test_verify_expired_otp_fails(): void
    {
        $user = User::factory()->create();

        $result = $this->otpService->verify($user->email, '123456');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('expired or not found', $result['message']);
    }

    public function test_verify_reset_token_succeeds_after_valid_otp(): void
    {
        $user = User::factory()->create();
        $otp = $this->otpService->generate($user->email);
        $verifyResult = $this->otpService->verify($user->email, $otp);

        $isValid = $this->otpService->verifyResetToken($user->email, $verifyResult['reset_token']);

        $this->assertTrue($isValid);
    }

    public function test_invalidate_reset_token_removes_token(): void
    {
        $user = User::factory()->create();
        $otp = $this->otpService->generate($user->email);
        $verifyResult = $this->otpService->verify($user->email, $otp);

        $this->otpService->invalidateResetToken($user->email);

        $this->assertFalse(
            $this->otpService->verifyResetToken($user->email, $verifyResult['reset_token'])
        );
    }

    public function test_generate_throws_for_unknown_identifier(): void
    {
        $this->expectException(ValidationException::class);

        $this->otpService->generate('unknown@example.com');
    }
}
