<?php

use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;

Route::controller(CompanyController::class)->group(function () {

    // --- راوتات عامة (Public Routes) ---
    Route::post('/upload-media', 'upload');
    Route::get('/initial-page', 'getFirstPage');
    Route::get('/forms_info', 'getFormDependencies');
    Route::post('/company_request', 'storeCompanyRequest');
    Route::get('/company_ready', 'promoteReadyRequests');
    Route::post('/event-request', 'storeEventRequest');
    Route::get('/sectors','getSectors');
    Route::get('/timeline','timeline');

    ///////////////////////////////////////////////////////////// for update the company request (action_required status)
    // جلب البيانات لكي يراها العميل قبل التعديل (GET)
    Route::get('request/amendment', 'verifyURL')
        ->name('api.company.request.amendment')
        ->middleware('signed');

    // إرسال التعديلات والملفات الجديدة (POST - with form-data)
    Route::post('request/amendment','updateCompanyRequest')
        ->middleware('signed');

    //////////////////////////////////////////////////////////////



    // --- راوتات محمية (Protected Routes) ---
    Route::middleware(['auth:sanctum', 'company.active'])->group(function () {

        // المنتجات (Products)
        Route::post('/store_product', 'storeProduct');
        Route::post('/update_product/{id}', 'updateProduct');
        Route::get('/products', 'getProduct');
        Route::delete('/delete_product/{id}', 'destroyProduct');

        // الملف الشخصي والداشبورد
        Route::post('/update_profile', 'updateProfile');
        Route::get('/company_home', 'getCompanyDashboard');
        Route::get('/company_profile', 'getCompanyProfile');

        // العروض (Promotions)
        Route::post('/store_promotion', 'storePromotion');
        Route::post('/update_promotion/{id}', 'updatePromotion');
        Route::delete('/delete_promotion/{id}', 'destroyPromotion');
        Route::get('/promotions', 'getPromotions');

        // الدفع المتبقي
        Route::get('/my-requests','myRequests');
        Route::post('/pay','payRemaining');
    });

    Route::post('/support-email', [VisitorController::class, 'SupportMessage']);
});
