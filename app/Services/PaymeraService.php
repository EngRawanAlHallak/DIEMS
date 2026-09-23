<?php

namespace App\Services;

use App\Jobs\Company\PromoteCompanyAndSendCredentialsJob;
use App\Models\CompanyRequest;
use App\Models\EventRequest;
use App\Models\Payment;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymeraService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $terminalId;

    public function __construct()
    {
        $this->baseUrl    = config('services.paymera.base_url');
        $this->username   = config('services.paymera.username');
        $this->password   = config('services.paymera.password');
        $this->terminalId = config('services.paymera.terminal_id');
    }

    /**
     * 1. إنشاء رابط دفع جديد عبر Paymera
     */
    public function createPayment(float $amount, string $callbackUrl, string $triggerUrl, string $notes = ''): array
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->acceptJson()
            ->post("{$this->baseUrl}/create-payment", [
                'lang'        => app()->getLocale(),
                'terminalId'  => $this->terminalId,
                'amount'      => $amount,
                'callbackURL' => $callbackUrl,
                'triggerURL'  => $triggerUrl,
                'notes'       => $notes,
            ]);

        if ($response->failed()) {
            Log::error('[Paymera API Error] Failed to create payment', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            throw new \Exception('Payment gateway creation failed.');
        }

        return $response->json();
    }

    /**
     * 2. الاستعلام عن حالة عملية دفع من Paymera
     */
    public function getPaymentStatus(string $paymeraPaymentId): array
    {
        Log::info("in service status method");
        $response = Http::withBasicAuth($this->username, $this->password)
            ->acceptJson()
            ->get("{$this->baseUrl}/get-payment-status/{$paymeraPaymentId}");

        if ($response->failed()) {
            Log::error('[Paymera API Error] Failed to get payment status', [
                'payment_id' => $paymeraPaymentId,
                'body'       => $response->body()
            ]);
            throw new \Exception('Failed to verify payment status with gateway.');
        }
        Log::info("result status method",[$response->json()]);
        return $response->json();
    }

    /**
     * 3. إلغاء عملية دفع في Paymera
     */
    public function cancelPayment(string $paymeraPaymentId): array
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->acceptJson()
            ->post("{$this->baseUrl}/cancel-payment", [
                'lang'       => app()->getLocale(),
                'payment_id' => $paymeraPaymentId,
            ]);

        return $response->json();
    }

    public function verifyAndProcessPayment(Payment $payment): bool
    {
        Log::info("in service : verify ");

        // Idempotency: إذا كان مدفوعاً مسبقاً، نعتبر العملية ناجحة مباشرة
        if ($payment->status === 'paid') {
            return true;
        }

        // التحقق من Paymera
        $statusResponse = $this->getPaymentStatus($payment->paymera_payment_id);

        $isSuccessful = isset($statusResponse['ErrorCode'], $statusResponse['Data']['status'])
            && $statusResponse['ErrorCode'] == 0
            && strtoupper($statusResponse['Data']['status']) === 'A';

        if ($isSuccessful) {
            DB::transaction(function () use ($payment, $statusResponse) {
                $payment->update([
                    'status'           => 'paid',
                    'gateway_response' => array_merge((array)$payment->gateway_response, ['webhook_verify' => $statusResponse]),
                ]);

                $payable = $payment->payable;

                if ($payable instanceof TicketOrder) {
                    $payable->update(['payment_status' => 'paid']);
                    $payable->tickets()->update(['status' => 'valid']);
                    Cache::forget("visitor:tickets:{$payable->guest_id}");

                } elseif ($payable instanceof CompanyRequest) {
                    /*$payable->update(['payment_status' => 'partial_paid']);
                    $payable->update(['paid_amount' => $payable->required_deposit]);
                    // 🚀 إطلاق الـ Job لترقية الشركة وإرسال الإيميل فوراً في الخلفية
                    PromoteCompanyAndSendCredentialsJob::dispatch($payable);
                    Log::info("company status updated to paid ");
                    // Cache::forget('admin:company_requests:*');*/

                    // 1. حساب المبلغ المدفوع الجديد تراكمياً
                    $newPaidAmount = $payable->paid_amount + $payment->amount;

                    // 2. تحديد الحالة: هل اكتمل المبلغ الإجمالي؟
                    $newStatus = ($newPaidAmount >= $payable->total_price) ? 'paid' : 'partial_paid';

                    $payable->update([
                        'paid_amount'    => $newPaidAmount,
                        'payment_status' => $newStatus,
                    ]);
                    Cache::forget("admin:company_request_detail:{$payable->id}");

                    // 3. التحقق مما إذا كان هذا هو "الدفع الأول" لإنشاء حساب الشركة
                    if (!$payable->company()->exists()) {
                        PromoteCompanyAndSendCredentialsJob::dispatch($payable);
                    }

                    // 4. مسح كاش لوحة تحكم هذه الشركة تحديداً
                    Cache::forget("company:{$payable->company->id}:requests_dashboard");
                    Log::info("Company Request updated: Amount Paid: {$newPaidAmount}, Status: {$newStatus}");

                }elseif ($payable instanceof EventRequest) {
                    $payable->update(['payment_status' => 'paid']);
                    Cache::forget("admin:event_request_detail:{$payable->id}");
                    Cache::forget("events:show:{$payable->id}");
                    Log::info("event status updated to paid ");
                }
                Log::info("done in service: verify ",['eventRequest' => $payable]);
            });
            return true;
        } else {
            $payment->update(['status' => 'failed']);
            Log::info("failed in service: verify ");
            return false;
        }
    }
}
