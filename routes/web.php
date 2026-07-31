<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// هذا هو الرابط الذي سترسلينه كـ callbackUrl لبوابة Paymera
// مثال: http://127.0.0.1:8000/payment/success
Route::get('/payment/success', function () {
    return view('payment.success');
})->name('payment.success');

// for initiat payment
Route::get('/payment/callback', [TicketController::class, 'handleCallback'])
    ->name('payment.callback');

// for remaining payment
Route::get('/payment/verify', [CompanyController::class, 'verifyPayment'])
    ->name('payment.verify');

Route::get('/company/payments/pay/{id}', [CompanyController::class, 'payDirectFromEmail'])
    ->name('company.payments.pay-direct')
    ->middleware('signed');

Route::get('/event/payments/pay/{id}', [EventController::class, 'payDirectFromEmail'])
    ->name('event.payments.pay-direct')
    ->middleware('signed');

// for webhook job (ngrok url)
Route::get('paymera/webhook/{payment_uuid}', [TicketController::class,'handleWebhook'])
    ->name('paymera.webhook');
