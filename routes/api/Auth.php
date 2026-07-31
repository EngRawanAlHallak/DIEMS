<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {

    Route::post('login','login')->middleware('throttle:5,1');// 5 times in 1 minites
    Route::post('forgot-password','forgotPassword');
    Route::post('reset-password','resetPassword');
    Route::post('verify-otp','verifyOtp');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout');
        Route::get('me','me');
    });
});
