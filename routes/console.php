<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $files = Storage::disk('local')->files('temp');
    foreach ($files as $file) {
        if (Storage::disk('local')->lastModified($file) < now()->week()->getTimestamp()) {
            Storage::disk('local')->delete($file);
        }
    }
})->weekly();

Schedule::call(function () {
    DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY mv_tickets_analytics;');
})->hourly();

//Schedule::command('events:release-expired')->hourly();
Schedule::command('app:update-expired-requests')->daily();
Schedule::command('app:cleanup-expired-payments')->hourly();
