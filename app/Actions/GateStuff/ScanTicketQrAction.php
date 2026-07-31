<?php

namespace App\Actions\GateStuff;

use App\Models\Ticket;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class ScanTicketQrAction
{
    use ApiResponse;
    public function execute(string $qrUuid)
    {
        $ticket = Ticket::with(['ticketType:id,name', 'ticketOrder:id,payment_status'])
            ->where('uuid', $qrUuid)
            ->first();

        // REQ-GE-008: إذا لم توجد التذكرة في قاعدة البيانات
        if (!$ticket) {
            return $this->invalidToken();
        }

        // REQ-GE-007: إذا كانت التذكرة مستخدمة مسبقاً
        if ($ticket->status === 'used') {

            $used_at = $ticket->used_at ? Carbon::parse($ticket->used_at)->format('Y-m-d H:i:s') : null;
            return $this->usedQR($used_at);
        }

        // REQ-GE-006: إذا كانت التذكرة صالحة (والطلب الخاص بها مدفوع)
        if ($ticket->status === 'valid' && $ticket->ticketOrder?->payment_status === 'paid') {

            // تحديث التذكرة مباشرة وبشكل Atomic في قاعدة البيانات
            $ticket->update([
                'status'        => 'used',
                'used_at'       => now(),
            ]);

            $data = [
                'visitor_name' => $ticket->visitor_name,
                'ticket_type'  => $ticket->ticketType->getTranslation('name', 'ar', false) ?? $ticket->ticketType->getTranslation('name', 'en', false),
            ];
            return $this->success($data , 'QR code GRANTED');
        }

        // حالة إضافية كحماية: التذكرة موجودة ولكن الطلب غير مدفوع أو ملغى
        return $this->failed('Invalid QR Code , its not paid or expired , check it please!!');
    }
}
