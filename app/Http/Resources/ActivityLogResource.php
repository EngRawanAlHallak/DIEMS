<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // فك تشفير الرسالة المترجمة
        $description = json_decode($this->description, true) ?? $this->description;

        return [
            'id'           => $this->id,
            'log_name'     => $this->log_name, // 'actions' أو 'system_errors'
            'event'        => $this->event,    // created, updated, deleted, failed
            'description'  => $description,

            // بيانات الشخص الذي قام بالعملية
            'causer'       => $this->causer ? [
                'id'    => $this->causer->id,
                'name'  => $this->causer->name ?? $this->causer->email ?? 'مستخدم',
                'type'  => class_basename($this->causer_type)
            ] : null,

            // العنصر المتأثر بالعملية (مثلاً Transportation)
            'subject'      => $this->subject ? [
                'id'   => $this->subject_id,
                'type' => class_basename($this->subject_type),
                'data' => $this->subject
            ] : null,

            // التفاصيل الإضافية وتفاصيل الأخطاء إن وجدت
            'properties'   => $this->properties,
            'created_at'   => $this->created_at->format('Y-m-d H:i:s'),
            'human_time'   => $this->created_at->diffForHumans(),
        ];
    }
}
