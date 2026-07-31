<?php

namespace App\Http\Requests\Contentmanagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExhibitionProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // حقول مترجمة أو نصوص عادية
            'name'             => 'sometimes|required',
            'session'          => 'sometimes|required|string|max:255',
            'address'          => 'sometimes|required',
            'bio'              => 'sometimes|required',

            // التواريخ والأوقات
            'start_date'       => 'sometimes|required|date',
            'end_date'         => 'sometimes|required|date|after_or_equal:start_date',
            'open_time'        => 'sometimes|required|date_format:H:i',
            'close_time'       => 'sometimes|required|date_format:H:i',

            // أرقام ومعلومات الاتصال
            'contact_email'    => 'sometimes|required|email|max:255',
            'contact_phone'    => 'sometimes|required|string|max:50',
            'emergency_phone'  => 'sometimes|required|string|max:50',

            // روابط التواصل الاجتماعي (اختيارية ويمكن إرسالها فارغة)
            'instagram_url'    => 'nullable|url|max:255',
            'facebook_url'     => 'nullable|url|max:255',
            'x_url'            => 'nullable|url|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'             => 'اسم المعرض',
            'session'          => 'دورة المعرض',
            'address'          => 'عنوان المعرض',
            'bio'              => 'نبذة عن المعرض',
            'start_date'       => 'تاريخ البداية',
            'end_date'         => 'تاريخ النهاية',
            'open_time'        => 'وقت الافتتاح',
            'close_time'       => 'وقت الإغلاق',
            'contact_email'    => 'البريد الإلكتروني للاتصال',
            'contact_phone'    => 'هاتف الاتصال',
            'emergency_phone'  => 'هاتف الطوارئ',
            'instagram_url'    => 'رابط انستغرام',
            'facebook_url'     => 'رابط فيسبوك',
            'x_url'            => 'رابط منصة X',
        ];
    }
}
