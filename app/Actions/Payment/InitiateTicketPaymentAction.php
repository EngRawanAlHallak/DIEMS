<?php

namespace App\Actions\Payment;

use App\Models\TicketOrder;
use App\Services\PaymeraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InitiateTicketPaymentAction
{
    public function __construct(protected PaymeraService $paymeraService) {}

    public function execute(string $orderUuid): array
    {
        $order = TicketOrder::where('uuid', $orderUuid)->firstOrFail();

        // 1. التحقق من صلاحية الطلب
        if ($order->payment_status === 'paid') {
            throw new \Exception('This order has already been paid.');
        }

        if ($order->expires_at && now()->greaterThan($order->expires_at)) {
            $order->update(['payment_status' => 'expired']);
            throw new \Exception('The booking session has expired. Please book again.');
        }

        // 2. إذا كان هناك دفع سابق قيد الانتظار ولم ينتهِ، نعيد نفس الرابط للأداء العالي
        $existingPayment = $order->payments()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingPayment && $existingPayment->payment_url) {
            return [
                'payment_url' => $existingPayment->payment_url,
                'expires_at'  => $existingPayment->expires_at,
            ];
        }

        // 3. التجهيز للاتصال بـ Paymera
        $paymentUuid = (string) Str::uuid();
        $callbackUrl = url("/payment/callback?payment_uuid={$paymentUuid}");
        //$webhookPath = route('paymera.webhook', ['payment_uuid' => $paymentUuid], false);
        $webhookPath = "https://webhook.site/c25d188f-f432-419a-951e-1a2fafc45f74";
        $triggerUrl  = env('NGROK_URL', config('app.url')) . $webhookPath;  //'https://webhook.site/e33fda29-729b-4088-80f9-b1bbe7c61a7f';  // دمج رابط ngrok مع مسار الـ Webhook (وفي حال عدم وجود ngrok، يعود للرابط الأساسي كحالة احتياطية)

        // طلب إنشاء الدفع من Service
        $paymeraResponse = $this->paymeraService->createPayment(
            amount: $order->total_amount,
            callbackUrl: $callbackUrl,
            triggerUrl: $triggerUrl,
            notes: "Ticket Order UUID: {$order->uuid}"
        );

        $paymeraId  = $paymeraResponse['Data']['paymentId'] ?? null;
        $paymentUrl = $paymeraResponse['Data']['url'] ?? null;

        if (!$paymentUrl || !$paymeraId) {
            \Illuminate\Support\Facades\Log::error('[Paymera API Error]', ['response' => $paymeraResponse]);
            throw new \Exception('Failed to generate payment link from gateway.');
        }

        // 4. تسجيل الدفع في قاعدة البيانات
        DB::transaction(function () use ($order, $paymeraId, $paymentUrl, $paymeraResponse,$paymentUuid) {
            $order->payments()->create([
                'uuid'               => $paymentUuid,
                'paymera_payment_id' => $paymeraId,
                'amount'             => $order->total_amount,
                'status'             => 'pending',
                'payment_url'        => $paymentUrl,
                'expires_at'         => $order->expires_at, // ينتهي بنهاية صلاحية حجز التذكرة (20 دقيقة)
                'gateway_response'   => $paymeraResponse,
            ]);
        });

        return [
            'payment_url' => $paymentUrl,
            'expires_at'  => $order->expires_at,
        ];
    }
}
