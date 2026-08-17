<?php

namespace Tests;

use App\Models\Company;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'company', 'gate_operator'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
        }
    }

    protected function createAdmin(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ], $attributes));

        $user->assignRole('admin');

        return $user;
    }

    protected function createCompanyUser(bool $active = true, array $userAttributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => Hash::make('password123'),
        ], $userAttributes));

        $user->assignRole('company');

        $sector = Sector::create([
            'name' => ['en' => 'Technology', 'ar' => 'تقنية'],
        ]);

        Company::create([
            'user_id' => $user->id,
            'name' => ['en' => 'Test Company', 'ar' => 'شركة تجريبية'],
            'responsible_person' => 'John Doe',
            'sector' => 'Technology',
            'sector_id' => $sector->id,
            'bio' => ['en' => 'Bio', 'ar' => 'وصف'],
            'nationality' => ['en' => 'Syrian', 'ar' => 'سوري'],
            'address' => ['en' => 'Damascus', 'ar' => 'دمشق'],
            'final_area' => 20.0,
            'booth_type' => 'Equipped Booth',
            'is_active' => $active,
        ]);

        return $user->fresh(['company']);
    }

    protected function actingAsAdmin(?User $user = null): static
    {
        $user ??= $this->createAdmin();

        return $this->actingAs($user, 'sanctum');
    }

    protected function actingAsCompany(?User $user = null, bool $active = true): static
    {
        $user ??= $this->createCompanyUser($active);

        return $this->actingAs($user, 'sanctum');
    }

    protected function withBearerToken(User $user): static
    {
        $token = $user->createToken('test-token', $user->getRoleNames()->toArray())->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function assertApiSuccess($response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure(['status', 'message', 'data'])
            ->assertJson(['status' => 'success']);
    }

    protected function assertApiValidationError($response): void
    {
        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors'])
            ->assertJson(['status' => 'error']);
    }
}
