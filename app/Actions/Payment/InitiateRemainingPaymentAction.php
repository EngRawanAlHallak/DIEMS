<?php

namespace App\Actions\Payment;

use App\Models\CompanyRequest;
use App\Services\PaymeraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InitiateRemainingPaymentAction
{
    public function __construct(protected PaymeraService $paymeraService) {}

    public function execute(int $id, float $amountToPay): array
    {
        $companyRequest = CompanyRequest::where('id', $id)->firstOrFail();
        // 1. حساب المبالغ المتبقية
        $paidAmount = $companyRequest->paid_amount ?? 0;
        $totalPrice = $companyRequest->total_price;
        $remainingAmount = $totalPrice - $paidAmount;

        // 2. التحقق من منطقية المبلغ (Security Check)
        if ($remainingAmount <= 0) {
            throw new \Exception('تم سداد كامل المبلغ مسبقاً، لا توجد دفعات مستحقة.');
        }

        if ($amountToPay > $remainingAmount) {
            throw new \Exception("المبلغ المدخل ({$amountToPay}) يتجاوز القيمة المتبقية المستحقة وهي ({$remainingAmount}).");
        }

        return DB::transaction(function () use ($companyRequest, $amountToPay, $remainingAmount) {
            // 3. تنظيف العمليات السابقة:
            // جلب أي عملية دفع "معلقة" وإلغائها في بوابة الدفع وقاعدة البيانات
            $pendingPayments = $companyRequest->payments()->where('status', 'pending')->get();

            foreach ($pendingPayments as $pending) {
                if ($pending->paymera_payment_id) {
                    try {
                        $this->paymeraService->cancelPayment($pending->paymera_payment_id);
                    } catch (\Exception $e) {
                        // تجاهل الخطأ الصامت في حال كانت ملغاة مسبقاً في Paymera
                        Log::error(["Paymera API Error : {$e->getMessage()}"]);

                    }
                }
                $pending->update(['status' => 'cancelled']);
            }

            // 4. تجهيز الروابط للعملية الجديدة
            $paymentUuid = (string) Str::uuid();
            //$callbackUrl = config('app.frontend_url') . "/company/payment/callback?payment_uuid={$paymentUuid}";
            $callbackUrl = url("/payment/verify?payment_uuid={$paymentUuid}");
            $webhookPath = route('paymera.webhook', ['payment_uuid' => $paymentUuid], false);
            $triggerUrl  = env('NGROK_URL', config('app.url')) . $webhookPath;

            // 5. إنشاء رابط الدفع من Paymera
            $paymeraResponse = $this->paymeraService->createPayment(
                amount: $amountToPay,
                callbackUrl: $callbackUrl,
                triggerUrl: $triggerUrl,
                notes: "Remaining Payment for Company Request ID: {$companyRequest->id}"
            );

            $paymeraId  = $paymeraResponse['Data']['paymentId'] ?? null;
            $paymentUrl = $paymeraResponse['Data']['url'] ?? null;

            // 6. حفظ العملية في الداتا بيز (صلاحية قصيرة لمدة ساعة مثلاً)
            $companyRequest->payments()->create([
                'uuid'               => $paymentUuid,
                'paymera_payment_id' => $paymeraId,
                'amount'             => $amountToPay,
                'status'             => 'pending',
                'payment_url'        => $paymentUrl,
                'expires_at'         => now()->addHour(), // صلاحية قصيرة كما طلبتِ
                'gateway_response'   => $paymeraResponse,
            ]);

            return [
                'payment_url' => $paymentUrl,
            ];
        });
    }
}
