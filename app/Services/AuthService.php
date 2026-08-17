<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Authenticate user by email + password.
     * Handles lockout check, failed attempt tracking, and token creation.
     *
     * @throws ValidationException
     */

    public function authenticate(string $loginKey, string $password, ?string $fcm_token = null): array
    {
        $isEmail = filter_var($loginKey, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'name';

        $user = User::select([
            'id', 'name', 'email', 'password',
        ])
            ->with('roles:id,name')
            ->where($field, $loginKey)
            ->first();

        // 1. User not found
        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['Invalid credentials.'],
            ]);
        }

        // 2. Admin login (username)
        if ($user->hasRole('admin')){
            if($isEmail)
                throw ValidationException::withMessages([
                    'identifier' => ['Invalid credentials, enter your username again.'],
                ]);

            $user->update(['fcm_token' => $fcm_token]);
        }

        if ($user->company && ! $user->company->is_active) {
            throw ValidationException::withMessages([
                'identifier' => ['Your company account has been deactivated by the administrator.'],
            ]);
        }

        // 3. Account locked
        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'identifier' => [
                    'message' => "Account locked for {$user->lockRemainingMinutes()} minutes due to too many failed attempts."
                ],
            ]);
        }

        // 4. Wrong password
        if (! Hash::check($password, $user->password)) {
            $user->recordFailedAttempt();
            throw ValidationException::withMessages([
                'identifier' => ['Invalid credentials, enter your password again.'],
            ]);
        }

        // 5. Success — reset lockout, create token
        $user->clearLockout();

        $token = $user->createToken(
            name:       'api-token',
            abilities:  $user->getRoleNames()->toArray(),
            expiresAt:  now()->addDays(7),
        )->plainTextToken;

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in successfully');

        return compact('user', 'token');
    }
}
