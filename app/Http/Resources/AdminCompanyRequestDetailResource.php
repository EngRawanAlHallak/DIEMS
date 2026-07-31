<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminCompanyRequestDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $isAdmin = $user && ($user->hasRole('admin'));
        return [
            'id'                  => $this->id,
            'foreign_local'       => $this->foreign_local,

            // 2. معالجة الحقول المترجمة بناءً على رتبة المستخدم (Ternary Operator)
            'company_name' => $isAdmin
                ? ($this->getTranslation('company_name', 'en', false) ?? $this->getTranslation('company_name', 'ar'))
                : $this->company_name,

            'nationality'  => $isAdmin
                ? ($this->getTranslation('nationality', 'en', false) ?? $this->getTranslation('nationality', 'ar'))
                : $this->nationality,

            'address'      => $isAdmin
                ? ($this->getTranslation('address', 'en', false) ?? $this->getTranslation('address', 'ar'))
                : $this->address,

            'company_description' => $isAdmin
                ? ($this->getTranslation('company_description', 'en', false) ?? $this->getTranslation('company_description', 'ar'))
                : $this->company_description,
            // البيانات الأساسية ومسؤول التواصل
            'responsible_name'    => $this->responsible_name,
            'job_title'           => $this->job_title,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'commercial_register' => $this->commercial_register,
            'sector'              => $this->sector, // إذا كان حقل نصي ثابت بقاعدة البيانات

            // بيانات المساحة والبثوث والاشتراطات
            'requested_area'      => $this->requested_area,
            'setup_preference'    => $this->setup_preference,
            'terms_accepted_at'   => $this->terms_accepted_at ? Carbon::parse($this->terms_accepted_at)->toDateTimeString() : null,

            // الحالات المالية وحالة الطلب
            'request_status'      => $this->request_status,
            'payment_status'      => $this->payment_status,
            //'total_price'         => (float) $this->total_price,
            //'required_deposit'    => (float) $this->required_deposit,
            //'paid_amount'         => (float) $this->paid_amount,
            //'payment_due_date'    => $this->payment_due_date,
            'requested_at'          => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),

            // قسم المستندات المرفوعة (تُعرض فقط إذا تم عمل Eager Load لها للأداء العالي)
            'documents'           => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    return [
                        'id'        => $document->id,
                        'file_type' => $document->file_type,
                        'file_url'  => $document->file_path ? Storage::disk('s3')->url($document->file_path) : null,
                    ];
                });
            }),
        ];
    }
}
