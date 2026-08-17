<?php

namespace App\Actions\Visitor;

use App\Actions\Payment\InitiateTicketPaymentAction;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InitialBookTicketsAction
{
    public function execute(array $data): array
    {
        // 1. معالجة الـ guest_id أو إنشائه إن لم يكن موجوداً
        $guestId = $data['guest_id'] ?? 'guest_' . Str::uuid();
        $lang = app()->getLocale();

        $lockKey ='ticket_booking_lock:' . md5($data['visitors'][0]['email']);

        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $secondsLeft = RateLimiter::availableIn($lockKey);
            $minutesLeft = ceil($secondsLeft / 60);

            $message = $lang == 'ar'
                ? "لقد قمت بتقديم طلب مؤخراً. يرجى الانتظار {$minutesLeft} دقيقة قبل المحاولة مجدداً أو إتمام الدفع."
                : "You have submitted a request recently. Please wait {$minutesLeft} minute(s) before trying again or completing the payment.";

            throw ValidationException::withMessages(["booking" => $message]);
        }

        $ticketType = TicketType::findOrFail($data['ticket_type_id']);
        if (count($data['visitors']) !== (int) $ticketType->persons_count) {
            $message = $lang == 'ar'
                ? "عدد الزوار المدخلين يجب أن يكون مساوياً لـ {$ticketType->persons_count} شخص."
                : "The number of visitors entered must be equal to {$ticketType->persons_count} people.";

            throw ValidationException::withMessages(["visitors" => $message]);
        }

        // تفعيل الحظر (Rate Limit) لمدة دقيقتين (120 ثانية) لأن البيانات سليمة وجاهزة للمعالجة
        RateLimiter::hit($lockKey, 120);

        return DB::transaction(function () use ($data, $guestId, $ticketType) {
            // إنشاء طلب التذكرة بحالة معلقة pending
            $order = TicketOrder::create([
                'uuid'           => (string) Str::uuid(),
                'guest_id'       => $guestId,
                'total_amount'   => $ticketType->price,
                'payment_status' => 'pending',
                'expires_at'     => now()->addMinutes(20), // صلاحية الرابط 20 دقيقة للدفع
            ]);

            //  إنشاء التذاكر المرتبطة بالطلب
            foreach ($data['visitors'] as $visitor) {
                Ticket::create([
                    'uuid'            => (string) Str::uuid(),
                    'ticket_order_id' => $order->id,
                    'ticket_type_id'  => $ticketType->id,
                    'visitor_name'    => $visitor['name'],
                    'visitor_email'   => $visitor['email'],
                    'visitor_phone'   => $visitor['phone'],
                    'interest_field'  => $visitor['interest_field'],
                    'status'          => 'valid', // تعتبر صالحة بمجرد دفع الطلب التابع لها
                ]);
            }

            /*return [
                'id'         => $order->id,
                'guest_id'   => $guestId,
                'order_uuid' => $order->uuid,
                'total_price'     => $order->total_amount,
                'payment_url' => url("/api/simulated-payment/" . $order->uuid)
            ];*/

            // استدعاء Action توليد رابط الدفع مباشرة
            $paymentData = app(InitiateTicketPaymentAction::class)->execute($order->uuid);

            return [
                'id'          => $order->id,
                'guest_id'    => $guestId,
                'order_uuid'  => $order->uuid,
                'total_price' => $order->total_amount,
                'payment_url' => $paymentData['payment_url'], // هذا هو رابط Paymera الحقيقي!
                'expires_at'  => $paymentData['expires_at'],
            ];
        });
    }
}
