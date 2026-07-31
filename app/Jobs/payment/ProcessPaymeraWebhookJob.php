<?php

namespace App\Jobs\payment;

use App\Models\Payment;
use App\Models\TicketOrder;
use App\Models\CompanyRequest;
use App\Services\PaymeraService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymeraWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $paymeraPaymentId) {}

    public function handle(PaymeraService $paymeraService): void
    {
        Log::info("in Paymera job");
        // 1. التثبت من وجود عملية الدفع في داتا بيز نظامنا
        $payment = Payment::where('paymera_payment_id', $this->paymeraPaymentId)->first();

        if (!$payment) {
            Log::warning("[Paymera Webhook] Payment record not found for Paymera ID: {$this->paymeraPaymentId}");
            return;
        }
        $payment = Payment::where('paymera_payment_id', $this->paymeraPaymentId)->first();

        if (!$payment) {
            Log::warning("[Paymera Webhook] Payment not found: {$this->paymeraPaymentId}");
            return;
        }
        try{
        // استدعاء دالة التحقق المشتركة
        $paymeraService->verifyAndProcessPayment($payment);
        Log::info("[Paymera Webhook] Payment successfully processed for Payment UUID: {$payment->uuid}");

        }catch (\Exception $exception){
            $payment->update(['status' => 'failed']);
        }

    }
}
