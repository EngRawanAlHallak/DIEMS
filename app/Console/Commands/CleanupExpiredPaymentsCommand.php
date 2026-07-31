<?php

namespace App\Console\Commands;

use App\Models\TicketOrder;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredPaymentsCommand extends Command
{
    // الاسم الذي سنشغل به الأمر من التيرمينال
    protected $signature = 'app:cleanup-expired-payments';
    protected $description = 'Clean up expired tickets and payments.';

    public function handle()
    {
        $this->info('Starting cleanup process...');
        Log::info('[Scheduled Job] Started cleanup of expired data.');

        try {
            DB::transaction(function () {
                $now = now();

                // 1. تنظيف طلبات التذاكر والتذاكر المرتبطة بها
                $expiredTicketOrders = TicketOrder::where('payment_status', '!=', 'paid')
                    ->where('expires_at', '<', $now)
                    ->get();

                foreach ($expiredTicketOrders as $order) {
                    $order->tickets()->delete();
                    $order->delete();
                }
                $this->info("Deleted {$expiredTicketOrders->count()} expired ticket orders.");

                // 2. تنظيف عمليات الدفع المنتهية وغير المدفوعة
                $deletedPayments = Payment::where('status', '!=', 'paid')
                    ->where('expires_at', '<', $now)
                    ->delete();
                $this->info("Deleted {$deletedPayments} expired payments.");
            });

            $this->info('Cleanup completed successfully!');
            Log::info('[Scheduled Job] Cleanup completed successfully.');

        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            Log::error('[Scheduled Job Error] ' . $e->getMessage());
        }
    }

}
