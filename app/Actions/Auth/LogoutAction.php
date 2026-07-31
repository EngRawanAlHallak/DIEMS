<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuthService;

class LogoutAction
{
    public function execute(User $user): void
    {
        $user->tokens()->delete();

        activity('auth')
            ->causedBy($user)
            ->log('User logged out from all devices');
    }
}
