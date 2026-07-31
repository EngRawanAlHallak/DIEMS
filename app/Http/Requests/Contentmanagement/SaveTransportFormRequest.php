<?php

namespace App\Http\Requests\Contentmanagement;

use Illuminate\Foundation\Http\FormRequest;

class SaveTransportFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // حقول إعدادات أوقات المواصلات العامة
            'transport_interval_minutes' => 'nullable|integer|min:1',
            'transport_start_time'       => 'nullable|date_format:H:i',
            'transport_end_time'         => 'nullable|date_format:H:i',

            // حقول خط المواصلات الفردي
            'name'                       => 'sometimes|required',
            'image'                      => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'google_maps_url'            => 'nullable|url|max:255', // <-- تم إضافته هنا
        ];
    }

    public function attributes(): array
    {
        return [
            'transport_interval_minutes' => 'الفارق الزمني للرحلات بالدقائق',
            'transport_start_time'       => 'وقت انطلاق أول رحلة',
            'transport_end_time'         => 'وقت انطلاق آخر رحلة',
            'name'                       => 'اسم خط المواصلات',
            'image'                      => 'صورة خط المواصلات',
            'google_maps_url'            => 'رابط خرائط جوجل', // <-- تم إضافته هنا
        ];
    }
}
