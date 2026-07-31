<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContentManagementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminController::class)->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Requests Screen (companies & events)
    Route::get('company-requests','getCompanyRequests');
    Route::get('company-requests/{id}','getCompanyDetails');
    Route::post('company-requests/update-status','updateCompanyRequestStatus');
    Route::get('event-requests','getEventRequests');
    Route::get('event-requests/{id}','getEventRequestDetails');

    //Route::post('approve-event-requests','approveEventRequest');

    // Halls Screen
    Route::get('sectors','getSectors');
    Route::post('sectors/add','addSector');
    Route::get('sectors/{id}','deleteSector');

    Route::get('halls/auto-assign','autoAssign');
    Route::get('halls','getHalls');
    Route::get('halls/{id}','getHallDetails');
    Route::post('halls/add','addHall');
    Route::post('halls/update','updateHall');
    Route::get('halls/delete/{id}','deleteHall');

    Route::post('halls/assignSector','attachSector');
    Route::post('halls/detach-sector','detachSector');
    Route::post('halls/createBooth','addBooth');
    Route::get('halls/booth/{id}','deleteBooth');
    Route::get('halls/booth/{id}/null','nullBooth');

    //Joined Screen (companies & events)
    Route::get('joined-companies','getJoinedCompanies');
    Route::patch('joined-companies/{company}/active-status','ActiveStatus');
    Route::get('joined-companies/{company}','JoinedCompanyDetails');
    Route::get('joined-companies/{company}/booths','suitableBooths');
    Route::post('assign-booth','assignBooth');

    Route::get('timeline','timeline');
    Route::post('assign-hall','assignHall');
    Route::post('add-slot','addSlot');

    Route::get('dashboard/statistics', 'getDashboardStats');

    Route::get('notifications','getNotifications');
    Route::get('notifications/{id}/read','markAsRead');
    Route::delete('notifications/{id}/delete','deleteNotification');

    Route::post('activity-logs',  'getActivityLogs');
    Route::get('activity-logs/{id}', 'showActivityLog');

    Route::post('database/backup',  'backup');
    Route::get('database/backup/files', 'getBackupFiles');
    Route::post('database/backup/download', 'downloadBackup');
    Route::post('database/backup/restore',  'restoreBackup');


});

    //tickets
Route::controller(TicketController::class)->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get('ticket/metrics','getMetrics');

    Route::get('ticket/get-types','getTicketTypes');
    Route::post('ticket/add-type','storeTicketType');
    Route::post('ticket/update-type/{id}','updateTicketType');
    Route::delete('ticket/delete-type/{id}','deleteTicketType');
    Route::get('ticket/status-type/{id}','toggleTypeStatus');

    Route::get('ticket/gate-code','generateGateCode');
});

Route::controller(ContentManagementController::class)->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('content/exhibition-profile','updateExhibitionProfile');
    Route::post('content/visitor-app-content','updateVisitorAppContent');
    Route::get('content/transportation', 'getTransportation');
    Route::post('content/transports/{transport?}', 'saveTransportForm');
    Route::delete('content/transports/{transport}', 'deleteTransport');
    Route::post('content/global-settings', 'updateGlobalSettings');
    Route::get('content/company-content', 'getFirstPage');
    Route::get('content/profile-page','getProfile');
    Route::get('content/welcome-page','welcomePage');


});

Route::controller(EventController::class)->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::post('events/update-status','updateStatus');
});

