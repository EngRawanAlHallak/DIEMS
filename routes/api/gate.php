<?php

use App\Http\Controllers\GateScannerController;
use Illuminate\Support\Facades\Route;

Route::controller(GateScannerController::class)->group(function () {

    Route::post('auth','authenticate');
    Route::post('scan','scanTicket');

});
