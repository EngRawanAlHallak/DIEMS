<?php

namespace App\Actions\Admin\ActivityLogs;

use App\Actions\General\BaseAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class GetActivityLogsAction extends BaseAction
{
    /**
     */
    public function execute(Request $request): Collection
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        // 1. الفلترة حسب نوع اللوج (actions / system_errors)
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // 2. الفلترة حسب الحالة (success / error)
        if ($request->filled('status')) {
            $query->where('properties->status', $request->status);
        }

        // 3. الفلترة حسب نوع الحدث (created, updated, deleted, failed)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // 4. الفلترة حسب الشخص المنفذ (causer_id)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // 5. البحث النصي داخل الوصف والـ properties
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                    ->orWhere('properties', 'LIKE', "%{$search}%");
            });
        }

        // 6. اختيار عدد محدد إذا أرسل الأدمن حقل limit (اختياري)
        if ($request->filled('limit')) {
            $query->take((int) $request->limit);
        }

        return $query->get();
    }
}
