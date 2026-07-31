<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Statistics\GetTicketMetricsAction;
use App\Actions\Admin\Tickets\InitialBookTicketsAction;
use App\Actions\Admin\Tickets\DeleteTicketTypeAction;
use App\Actions\Admin\Tickets\GetActiveTicketTypesAction;
use App\Actions\Admin\Tickets\GetTicketTypesAction;
use App\Actions\Admin\Tickets\GetVisitorTicketsAction;
use App\Actions\Admin\Tickets\ToggleTicketTypeStatusAction;
use App\Actions\Admin\Tickets\UpdateTicketTypeAction;
use App\Actions\Admin\Tickets\CreateTicketTypeAction;
use App\Actions\Admin\Tickets\GenerateDailyGateCodeAction;
use App\Actions\Payment\InitiateTicketPaymentAction;
use App\Http\Requests\Admin\AddTicketTypeRequest;
use App\Http\Requests\Admin\BookTicketRequest;
use App\Http\Requests\Admin\UpdateTicketTypeRequest;
use App\Jobs\payment\ProcessPaymeraWebhookJob;
use App\Models\Payment;
use App\Services\PaymeraService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    use ApiResponse;

    ////for admin
    public function getMetrics(GetTicketMetricsAction $action): JsonResponse
    {
        $data = $action->execute();
        return $this->success($data, 'Ticket Statistics fetched successfully');
    }

    public function getTicketTypes(GetTicketTypesAction $action): JsonResponse
    {
        $types = $action->execute();
        return $this->success($types, 'Ticket Types fetched successfully');
    }

    public function storeTicketType(AddTicketTypeRequest $request, CreateTicketTypeAction $action): JsonResponse
    {
        $ticketType = $action->execute($request->validated());
        return $this->success($ticketType, 'Ticket type created successfully');
    }

    public function updateTicketType(UpdateTicketTypeRequest $request, $id, UpdateTicketTypeAction $action)
    {
        $action->execute($id, $request->validated());
        return $this->success(null, 'Ticket updated successfully');
    }

    public function toggleTypeStatus($id, ToggleTicketTypeStatusAction $action)
    {
        $action->execute($id);
        return $this->success(null, 'Ticket Status updated successfully');
    }

    public function deleteTicketType($id, DeleteTicketTypeAction $action)
    {
        $action->execute($id);
        return $this->success(null, 'Ticket deleted successfully');
    }

    public function generateGateCode(GenerateDailyGateCodeAction $action): JsonResponse
    {
        $gateCode = $action->execute();
        return $this->success($gateCode, 'Gate Code created successfully');
    }

    ////////////////////////////////////////////////////////////////for visitor

    public function getTypes(GetActiveTicketTypesAction $action)
    {
        $types = $action->execute();
        return $this->success($types, 'ticket types fetched successfully');
    }
    public function book(BookTicketRequest $request, InitialBookTicketsAction $action)
    {
        $order = $action->execute($request->validated());
        return $this->success($order, 'pay for tickets please');

    }
    public function myTickets(Request $request, GetVisitorTicketsAction $action)
    {
        $tickets = $action->execute($request->header('X-Guest-ID'));
        return $this->success($tickets, 'Tickets fetched successfully');
    }

    /////////////////////////////////////////////////////////// payment

    public function payTicket(Request $request, InitiateTicketPaymentAction $action): JsonResponse
    {
        $request->validate(['order_uuid' => 'required|uuid|exists:ticket_orders,uuid']);

        $result = $action->execute($request->order_uuid);

        return $this->success($result, 'Tickets payment successfully');
    }

    public function handleWebhook(string $payment_uuid): JsonResponse
    {
        Log::info("[controller] URL parameter payment_uuid: {$payment_uuid}");

        // 1. الوصول المباشر لعملية الدفع!
        $payment = Payment::where('uuid', $payment_uuid)->first();

        if (!$payment) {
            Log::error("[Paymera Webhook] Payment record not found for UUID: {$payment_uuid}");
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $paymeraPaymentId = $payment->paymera_payment_id;
        ProcessPaymeraWebhookJob::dispatch($paymeraPaymentId);

        return response()->json(['status' => 'success', 'message' => 'Webhook received']);
    }

    public function handleCallback(Request $request, PaymeraService $paymeraService)
    {
        Log::info("in controller ");

        $paymentUuid = $request->query('payment_uuid');

        if (!$paymentUuid) {
            return abort(400, 'Invalid request, missing payment UUID.');
        }

        $payment = Payment::where('uuid', $paymentUuid)->first();

        if (!$payment) {
            return abort(404, 'Payment record not found.');
        }

        $isPaid = $paymeraService->verifyAndProcessPayment($payment);
        Log::info("in controller , out of service ,is paid{$isPaid} ");

        // 3. بناءً على النتيجة نعرض الواجهة المناسبة
        if ($isPaid) {
            return view('payment.success');
        }
        /* في الـ API: ستحولين دالة handleCallback التي كتبناها في الأعلى من كونها ترجع view('payment.success') إلى إرجاع response()->json(['status' => 'success']). سيقوم الفرونت إند باستدعاء هذا الـ API بمجرد تحميل صفحته ليتأكد من الدفع ويعرض الواجهة الخضراء من طرفه.*/

        // في حال فشل الدفع أو تم الإلغاء
        return abort(400, 'Payment failed or was cancelled.');
    }
}
