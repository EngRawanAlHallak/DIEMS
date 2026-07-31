<?php

namespace App\Actions\Admin\Statistics;

use App\Models\TicketOrder;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;

class GetTicketMetricsAction
{
    public function execute(): array
    {
        // استخدام الكاش لمدة 10 دقائق لتخفيف الضغط على الداتا بيز (يتحدث تلقائياً)
        return Cache::remember('admin_ticket_metrics', 600, function () {

            $totalRevenue = TicketOrder::where('payment_status', 'paid')->sum('total_amount');

            $ticketsSoldToday = Ticket::whereHas('ticketOrder', function ($query) {
                $query->where('payment_status', 'paid');
            })->whereDate('created_at', today())->count();

            $totalTickets = Ticket::whereHas('ticketOrder', function ($query) {
                $query->where('payment_status', 'paid');
            })->count();

            $percentageByType = Ticket::selectRaw('ticket_type_id, count(*) as count')
                ->whereHas('ticketOrder', function ($query) {
                    $query->where('payment_status', 'paid');
                })
                ->with('ticketType:id,name') // Eager Loading
                ->groupBy('ticket_type_id')
                ->get()
                ->map(function ($item) use ($totalTickets) {
                    return [
                        'type_name' => $item->ticketType->getTranslation('name', 'en'),
                        'percentage' => $totalTickets > 0 ? round(($item->count / $totalTickets) * 100, 2) : 0
                    ];
                });

            return [
                'total_revenue' => $totalRevenue,
                'daily_sales' => $ticketsSoldToday,
                'ticket_type_sales' => $percentageByType
            ];
        });
    }
}
