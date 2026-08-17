<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EventRequest;
use App\Models\EventSlot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredEventSlotsCommand extends Command
{
    protected $signature = 'events:release-expired';

    protected $description = 'تحرير الفتحات الزمنية للفعاليات المقبولة التي انتهت مهلة دفعها (48 ساعة) ولم تدفع';

    public function handle(): int
    {
        Log::info('بدء فحص طلبات الفعاليات منتهية الصلاحية...');
        $expiredRequests = EventRequest::where('request_status', 'approved')
            ->where('payment_status', 'unpaid')
            ->where('payment_due_date', '<', now())
            ->get();

        if ($expiredRequests->isEmpty()) {
            $this->info('لا توجد أي طلبات منتهية الصلاحية حالياً.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($expiredRequests as $request) {
            DB::transaction(function () use ($request, &$count) {
                $request->update([
                    'request_status' => 'rejected', // أو إضافة حالة 'expired' في الـ enum
                ]);
                EventSlot::where('id', $request->slot_id)->update([
                    'available' => true
                ]);

                $count++;
                Cache::forget("admin:event_request_detail:{$request->id}");
                Log::info("تم إلغاء الطلب رقم (#{$request->id}) وتحرير الفتحة الزمنية رقم ({$request->slot_id}) بسبب عدم الدفع.");
            });
        }

        Cache::forget("admin:events_timeline:all");
        Cache::forget("company:events_timeline:all");
        $this->info("تم بنجاح تحرير وإلغاء {$count} طلبات فعاليات منتهية الصلاحية.");
        return Command::SUCCESS;
    }
}
