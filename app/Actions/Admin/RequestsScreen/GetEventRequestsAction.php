<?php

namespace App\Actions\Admin\RequestsScreen;

use App\Http\Resources\EventRequestDetailsResource;
use App\Models\EventRequest;

class GetEventRequestsAction
{
    public function execute(array $filters)
    {
        // نستخدم md5 لضمان أن المفتاح ليس طويلاً جداً
        //$cacheKey = 'admin:company_requests:' . md5(json_encode($filters));
        //Cache::forget($cacheKey);
        //Cache::tags(['company_requests'])->flush();
        //return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters) {

            $query = EventRequest::query()
                ->with('sector:id,name')

                // فلتر حالة الطلب (نتجاهل 'all')
                ->when(!empty($filters['request_status']) && $filters['request_status'] !== 'all', function ($query) use ($filters) {
                    $query->where('request_status', $filters['request_status']);
                })

                // فلتر حالة الدفع (نتجاهل 'all')
                ->when(!empty($filters['payment_status']) && $filters['payment_status'] !== 'all', function ($query) use ($filters) {
                    $query->where('payment_status', $filters['payment_status']);
                })

                // فلتر التاريخ (تاريخ الإنشاء)
                ->when(!empty($filters['date']), function ($query) use ($filters) {
                    $query->whereDate('created_at', $filters['date']);
                })

                // فلتر البحث بالاسم (عربي أو إنجليزي داخل حقل الـ JSONB)
                ->when(!empty($filters['search']), function ($query) use ($filters) {
                    $searchTerm = $filters['search'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('event_title->en', 'ilike', "%{$searchTerm}%")
                            ->orWhere('event_title->ar', 'ilike', "%{$searchTerm}%");
                    });
                })
                // ترتيب النتائج من الأحدث للأقدم
                ->latest();

            $paginatedData = $query->paginate(5, ['*'], 'page', $filters['page'] ?? 1);
            return [
                // تحويل الموديلات إلى Resource واستخدام resolve() لتحويلها لمصفوفة فوراً
                'items' => EventRequestDetailsResource::collection($paginatedData->items())->resolve(),
                'pagination' => [
                    'current_page' => $paginatedData->currentPage(),
                    'last_page'    => $paginatedData->lastPage(),
                    'total'        => $paginatedData->total(),
                ]
            ];
        //});
    }
}
