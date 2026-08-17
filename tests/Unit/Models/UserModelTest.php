<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_is_not_locked_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->isLocked());
        $this->assertSame(0, $user->lockRemainingMinutes());
    }

    public function test_user_is_locked_when_locked_until_is_in_future(): void
    {
        $user = User::factory()->locked()->create();

        $this->assertTrue($user->isLocked());
        $this->assertGreaterThan(0, $user->lockRemainingMinutes());
    }

    public function test_record_failed_attempt_increments_counter(): void
    {
        $user = User::factory()->create(['failed_attempts' => 0]);

        $user->recordFailedAttempt();

        $this->assertSame(1, $user->fresh()->failed_attempts);
    }

    public function test_account_locks_after_max_failed_attempts(): void
    {
        $user = User::factory()->create(['failed_attempts' => User::MAX_ATTEMPTS - 1]);

        $user->recordFailedAttempt();

        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertSame(0, $user->failed_attempts);
    }

    public function test_clear_lockout_resets_lock_fields(): void
    {
        $user = User::factory()->locked()->create(['failed_attempts' => 2]);

        $user->clearLockout();

        $user->refresh();
        $this->assertFalse($user->isLocked());
        $this->assertSame(0, $user->failed_attempts);
        $this->assertNull($user->last_failed_at);
    }

    public function test_user_has_company_relationship(): void
    {
        $user = $this->createCompanyUser();

        $this->assertNotNull($user->company);
        $this->assertTrue($user->company->is_active);
    }
}
