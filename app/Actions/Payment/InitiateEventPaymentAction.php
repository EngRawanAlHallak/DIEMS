<?php

namespace App\Actions\Payment;

use App\Models\EventRequest;
use App\Services\PaymeraService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InitiateEventPaymentAction
{
    public function __construct(protected PaymeraService $paymeraService) {}

    public function execute(int $eventRequestID): array
    {
        $eventRequest = EventRequest::where('id', $eventRequestID)->firstOrFail();

        // 1. التحقق من الدفع المسبق
        if (in_array($eventRequest->payment_status, ['paid', 'partial_paid'])) {
            throw new \Exception('This event registration fee has already been paid.');
        }

        // 2. التحقق من شرط الـ 72 ساعة (payment_due_date)
        if ($eventRequest->payment_due_date && now()->greaterThan($eventRequest->payment_due_date)) {

            // إلغاء أي عملية دفع معلقة لدى Paymera إن وجدت
            $pendingPayment = $eventRequest->payments()->where('status', 'pending')->first();
            if ($pendingPayment && $pendingPayment->paymera_payment_id) {
                try {
                    $this->paymeraService->cancelPayment($pendingPayment->paymera_payment_id);
                    $pendingPayment->update(['status' => 'expired']);
                } catch (\Exception $e) {
                    // Log fail silently
                }
            }

            // تحديث حالة طلب الشركة إلى "منتهي الصلاحية"
            $eventRequest->update(['status' => 'expired']);
            Cache::forget("admin:event_request_detail:{$eventRequest->id}");
            throw new \Exception('The payment link has expired (72 hours exceeded). Please contact exhibition support.');
        }

        // 3. التحقق من وجود عملية دفع معلقة وغير منتهية لعدم تكرار الطلبات
        $existingPayment = $eventRequest->payments()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingPayment && $existingPayment->payment_url) {
            return [
                'payment_url' => $existingPayment->payment_url,
                'expires_at'  => $existingPayment->expires_at,
            ];
        }

        $paymentUuid = (string) Str::uuid();
        //$callbackUrl = config('app.frontend_url') . "/payment/callback?type=company&id={$companyRequest->id}";
        $callbackUrl = url("/payment/callback?payment_uuid={$paymentUuid}");
        $webhookPath = "https://webhook.site/c25d188f-f432-419a-951e-1a2fafc45f74";
        //$webhookPath = route('paymera.webhook', ['payment_uuid' => $paymentUuid], false);
        $triggerUrl  = env('NGROK_URL', config('app.url')) . $webhookPath;

        // المبلغ المطلوب (الدفعة الأولى Required Deposit مثلاً)
        $amountToPay = $eventRequest->total_price;

        $paymeraResponse = $this->paymeraService->createPayment(
            amount: $amountToPay,
            callbackUrl: $callbackUrl,
            triggerUrl: $triggerUrl,
            notes: "Company Application ID: {$eventRequestID}"
        );

        $paymeraId  = $paymeraResponse['Data']['paymentId'] ?? null;
        $paymentUrl = $paymeraResponse['Data']['url'] ?? null;

        // 5. حفظ عملية الدفع وتحديد ينتهي بعد 72 ساعة (أو ما تبقى منها)
        DB::transaction(function () use ($eventRequest, $amountToPay, $paymeraId, $paymentUrl, $paymeraResponse, $paymentUuid) {
            $eventRequest->payments()->create([
                'uuid'               => $paymentUuid,
                'paymera_payment_id' => $paymeraId,
                'amount'             => $amountToPay,
                'status'             => 'pending',
                'payment_url'        => $paymentUrl,
                'expires_at'         => $eventRequest->payment_due_date, // تاريخ الـ 72 ساعة
                'gateway_response'   => $paymeraResponse,
            ]);
        });

        return [
            'payment_url' => $paymentUrl,
            //'expires_at'  => $companyRequest->payment_due_date,
        ];
    }
}
