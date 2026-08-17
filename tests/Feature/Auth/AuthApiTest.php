<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_company_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createCompanyUser();
        $user->update(['password' => Hash::make('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonStructure([
            'data' => ['user', 'token'],
        ]);
    }

    public function test_admin_can_login_with_username_and_fcm_token(): void
    {
        $admin = $this->createAdmin();
        $admin->update(['password' => Hash::make('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => $admin->name,
            'password' => 'password123',
            'fcm_token' => 'test-fcm-token',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.user.name', $admin->name);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $user = $this->createCompanyUser();
        $user->update(['password' => Hash::make('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertApiValidationError($response);
    }

    public function test_login_validation_requires_identifier_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $this->assertApiValidationError($response);
        $response->assertJsonValidationErrors(['identifier', 'password']);
    }

    public function test_admin_login_requires_fcm_token(): void
    {
        $admin = $this->createAdmin();
        $admin->update(['password' => Hash::make('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => $admin->name,
            'password' => 'password123',
        ]);

        $this->assertApiValidationError($response);
    }

    public function test_authenticated_user_can_get_profile_via_me(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->getJson('/api/auth/me');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.email', $user->email);
    }

    public function test_unauthenticated_me_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createCompanyUser();
        Sanctum::actingAs($user, ['company']);

        $response = $this->postJson('/api/auth/logout');

        $this->assertApiSuccess($response);
        $response->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_forgot_password_requires_identifier(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $this->assertApiValidationError($response);
    }

    public function test_verify_otp_requires_six_digit_code(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/verify-otp', [
            'identifier' => $user->email,
            'otp' => '12',
        ]);

        $this->assertApiValidationError($response);
    }

    public function test_reset_password_requires_valid_fields(): void
    {
        $response = $this->postJson('/api/auth/reset-password', []);

        $this->assertApiValidationError($response);
    }

    public function test_locked_user_cannot_login(): void
    {
        $user = User::factory()->locked()->create([
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('company');

        $response = $this->postJson('/api/auth/login', [
            'identifier' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertApiValidationError($response);
    }
}
