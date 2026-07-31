<?php

namespace App\Console\Commands;

use App\Jobs\Notification\SendExpiredNotificationEmailJob;
use App\Models\EventSlot;
use Illuminate\Console\Command;
use App\Models\TicketOrder;
use App\Models\Payment;
use App\Models\CompanyRequest;
use App\Models\EventRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateExpiredRequestsCommand extends Command
{
    // الاسم الذي سنشغل به الأمر من التيرمينال
    protected $signature = 'app:update-expired-requests';
    protected $description = 'update expired requests statuses.';

    public function handle()
    {
        $this->info('Starting cleanup process...');
        Log::info('[Scheduled Job] Started cleanup of expired data.');

        try {
            DB::transaction(function () {
                $now = now();

                $expiredCompanyRequests = CompanyRequest::where('request_status', 'approved')
                    ->whereIn('payment_status', ['unpaid', 'partial_paid'])
                    ->where('payment_due_date', '<', $now)
                    ->lockForUpdate()
                    ->get();

                foreach ($expiredCompanyRequests as $request) {
                    $request->update(['request_status' => 'expired']);

                    // إطلاق Job إرسال الإيميل للشركة
                    SendExpiredNotificationEmailJob::dispatch($request->email, $request->getTranslation('company_name', 'en', false) ?? $request->getTranslation('company_name', 'ar'), 'company');
                }
                $this->info("Updated {$expiredCompanyRequests->count()} company requests to expired.");

                $expiredEventRequests = EventRequest::where('request_status', 'approved')
                    ->whereIn('payment_status', ['unpaid', 'partial_paid'])
                    ->where('payment_due_date', '<', $now)
                    ->lockForUpdate()
                    ->get();

                foreach ($expiredEventRequests as $request) {
                    $request->update(['request_status' => 'expired']);

                    // تحرير السلوت ليعود متاحاً
                    EventSlot::where('id', $request->slot_id)->update(['available' => true]);

                    // إطلاق Job إرسال الإيميل لمنظم الفعالية
                    SendExpiredNotificationEmailJob::dispatch($request->organizer_email, $request->organizer_name, 'event');
                }
                $this->info("Updated {$expiredEventRequests->count()} event requests to expired.");
            });

            $this->info('Cleanup completed successfully!');
            Log::info('[Scheduled Job] Cleanup completed successfully.');

        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            Log::error('[Scheduled Job Error] ' . $e->getMessage());
        }
    }
}
