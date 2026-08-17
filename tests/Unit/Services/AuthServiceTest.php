<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function test_authenticate_company_user_with_email_succeeds(): void
    {
        $user = $this->createCompanyUser();
        $user->update(['password' => Hash::make('SecurePass123')]);

        $result = $this->authService->authenticate($user->email, 'SecurePass123');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertSame($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_authenticate_admin_with_username_succeeds(): void
    {
        $admin = $this->createAdmin();
        $admin->update(['password' => Hash::make('AdminPass123')]);

        $result = $this->authService->authenticate($admin->name, 'AdminPass123', 'fcm-test-token');

        $this->assertSame($admin->id, $result['user']->id);
        $this->assertSame('fcm-test-token', $admin->fresh()->fcm_token);
    }

    public function test_admin_cannot_login_with_email(): void
    {
        $admin = $this->createAdmin();
        $admin->update(['password' => Hash::make('AdminPass123')]);

        $this->expectException(ValidationException::class);

        $this->authService->authenticate($admin->email, 'AdminPass123');
    }

    public function test_authenticate_throws_for_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123'),
        ]);
        $user->assignRole('company');

        $this->expectException(ValidationException::class);

        $this->authService->authenticate($user->email, 'WrongPass123');
    }

    public function test_authenticate_throws_when_account_is_locked(): void
    {
        $user = User::factory()->locked()->create([
            'password' => Hash::make('SecurePass123'),
        ]);
        $user->assignRole('company');

        $this->expectException(ValidationException::class);

        $this->authService->authenticate($user->email, 'SecurePass123');
    }

    public function test_failed_login_records_attempt(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SecurePass123'),
            'failed_attempts' => 0,
        ]);
        $user->assignRole('company');

        try {
            $this->authService->authenticate($user->email, 'WrongPass123');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame(1, $user->fresh()->failed_attempts);
    }

    public function test_successful_login_clears_lockout(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SecurePass123'),
            'failed_attempts' => 2,
            'locked_until' => null,
        ]);
        $user->assignRole('company');

        $this->authService->authenticate($user->email, 'SecurePass123');

        $user->refresh();
        $this->assertSame(0, $user->failed_attempts);
        $this->assertNull($user->locked_until);
    }
}
