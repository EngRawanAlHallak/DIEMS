<?php

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use App\Services\AuthService;

class LoginAction
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function execute(string $loginKey, string $password, ?string $fcm_token = null): array
    {
        ['user' => $user, 'token' => $token] = $this->authService->authenticate($loginKey, $password, $fcm_token);

        return [
            'token'      => $token,
            'user'       => new UserResource($user),
        ];
    }
}
