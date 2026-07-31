<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::controller(VisitorController::class)->group(function () {
    Route::get('welcome-page','welcomePage');
    Route::get('home-page','homePage');
    Route::get('profile-page','getProfile');
    Route::get('companies','getCompanies');
    Route::get('companies/{company}','companyDetails');
    Route::post('support-email','SupportMessage');
    Route::get('transportation','getTranportation');
});

Route::controller(EventController::class)->group(function () {
    Route::get('events','eventsByDate');
    Route::get('events/{id}','eventDetails');
});

Route::controller(TicketController::class)->group(function () {
    Route::get('tickets/types','getTypes');
    Route::get('tickets/my-tickets','myTickets');
    Route::post('tickets/book','book');
});


