<?php

namespace App\Http\Controllers;


use App\Actions\Payment\InitiateEventPaymentAction;
use App\Actions\Visitor\GetEventDetailAction;
use App\Actions\Visitor\GetEventsByDateAction;
use App\Services\UpdateEventStatusService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    use ApiResponse;

    public function eventsByDate(Request $request,GetEventsByDateAction $action): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:d-m-Y'],
        ]);
        $date = $validated['date'];
        $formatedDate = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        $events = $action->execute($formatedDate);

        return $this->success($events,
            'Events fetched successfully'
        );
    }

    public function eventDetails(int $id, GetEventDetailAction $action): JsonResponse
    {
        $event = $action->execute($id);

        return $this->success($event,
            'Event detail fetched successfully'
        );
    }

    public function updateStatus(Request $request, UpdateEventStatusService $service)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:event_requests,id',
            'status' => 'required|in:approved,rejected,cancelled',
        ]);

        $service->updateStatus($validated['request_id'], $validated['status']);

        return $this->success(null, 'Event status updated successfully'
        );
    }

    public function payDirectFromEmail(int $id, InitiateEventPaymentAction $action)
    {
        try {
            // استدعاء الأكشن للحصول على رابط بوابة الدفع
            $paymeraData = $action->execute($id);
            //return $paymeraUrl;
            $paymeraUrl = is_array($paymeraData) ? $paymeraData['payment_url'] : $paymeraData;
            return redirect()->away($paymeraUrl);

        } catch (\Exception $e) {
            Log::error("[1-Click Payment Error] " . $e->getMessage());

            // في حال الخطأ، أو الدفع المسبق، نعيده لصفحة خطأ في الفرونت إند
            //$errorUrl = config('app.frontend_url') . "/payment/error?message=" . urlencode($e->getMessage());
            //return redirect()->away($errorUrl);
        }
    }


}
