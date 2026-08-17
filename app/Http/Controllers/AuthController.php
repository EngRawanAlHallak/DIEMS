<?php

namespace App\Http\Controllers;

use App\Actions\Auth\ForgotPasswordAction;
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\VerifyOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Jobs\Notification\SendAdminNotificationJob;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LoginAction  $loginAction,
        private readonly LogoutAction $logoutAction,
        private readonly ForgotPasswordAction $forgotAction,
        private readonly ResetPasswordAction  $resetAction,
        private readonly VerifyOtpAction      $verifyOtpAction,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->execute(
            loginKey: $request->validated('identifier'),
            password: $request->validated('password'),
            fcm_token: $request->validated('fcm_token'),
        );

        return $this->success($result, 'Login successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutAction->execute(
            user:       $request->user()
        );

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        // Eager load roles — zero N+1
        $user = $request->user()->load('roles:id,name');

        return $this->success(new UserResource($user));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->forgotAction->execute($request->validated('identifier'));

        return $this->success(
            null,
            'an OTP has been sent to your email and phone.'
        );
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'otp'   => ['required', 'string', 'digits:6'],
        ]);

        $resetToken = $this->verifyOtpAction->execute(
            identifier: $validated['identifier'],
            otp:   $validated['otp'],
        );

        return $this->success(
            ['reset_token' => $resetToken],
            'OTP verified. Use reset_token to set your new password within 5 minutes.'
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->resetAction->execute(
            identifier:       $request->validated('identifier'),
            resetToken:  $request->validated('resetToken'),
            newPassword: $request->validated('password'),
        );

        return $this->success(null, 'Password reset successfully. Please login with your new password.');
    }
}
