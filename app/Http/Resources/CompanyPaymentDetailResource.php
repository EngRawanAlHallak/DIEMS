<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CompanyPaymentDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $remainingAmount = $this->total_price - $this->paid_amount;

        return [
            'id'               => $this->id,
            'request_status'   => $this->request_status,
            'payment_status'   => $this->payment_status,

            // التفاصيل المالية
            'financials' => [
                'total_price'      => (float) $this->total_price,
                'required_deposit' => (float) $this->required_deposit,
                'paid_amount'      => (float) $this->paid_amount,
                'remaining_amount' => (float) max(0, $remainingAmount),
                'due_date'         => $this->payment_due_date ? Carbon::parse($this->payment_due_date)->format('Y-m-d') : null,
            ],

            // تفاصيل البوث والمساحة المحجوزة
            'booth_details' => $this->whenLoaded('booth', function () {
                return $this->booth ? [
                    'booth_number'   => $this->booth->booth_number,
                    'type'           => $this->booth->booth_type,
                    'equipment_type' => $this->booth->equipment_type,
                    'size_sqm'       => $this->booth->size_sqm,
                ] : [
                    // في حال لم يتم تخصيص بوث من الأدمن بعد
                    'requested_area'   => $this->requested_area,
                    'setup_preference' => $this->setup_preference,
                ];
            }),

            // سجل الدفعات (Transaction History)
            'payment_history' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    return [
                        'transaction_id' => $payment->paymera_payment_id,
                        'amount'         => (float) $payment->amount,
                        'currency'       => $payment->currency,
                        'date'           => Carbon::parse($payment->created_at)->format('Y-m-d H:i a'),
                    ];
                });
            }),
        ];
    }
}
