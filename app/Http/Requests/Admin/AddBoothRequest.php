<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddBoothRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booth_number'   => ['required','string','unique:booths,booth_number'],
            'booth_type'     => ['required','in:sales,display'],
            'equipment_type' => ['required','in:Equipped Booth,Not Equipped Booth,Row Space Only,Kiosk AB,Kiosk CD'],
            'size_sqm'       => ['required','numeric'],
            'sector_id'      => ['required','exists:sectors,id'],
            'hall_id'      => ['required','exists:halls,id'],
            'available'      => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            // رقم البوث
            'booth_number.required' => 'يرجى إدخال رقم الجناح (Booth Number).',
            'booth_number.string' => 'يجب أن يكون رقم الجناح نصاً صالحاً.',
            'booth_number.unique' => 'رقم الجناح هذا مسجل مسبقاً، يرجى اختيار رقم آخر.',

            // نوع البوث
            'booth_type.required' => 'يرجى تحديد نوع الجناح.',
            'booth_type.in' => 'نوع الجناح المحدد غير صالح (يجب أن يكون مبيعات sales أو عرض display).',

            // نوع التجهيزات
            'equipment_type.required' => 'يرجى اختيار نوع تجهيزات الجناح.',
            'equipment_type.in' => 'نوع التجهيزات المختارة غير مدعومة في النظام.',

            // المساحة
            'size_sqm.required' => 'يرجى إدخال مساحة الجناح بالمتر المربع.',
            'size_sqm.numeric' => 'يجب أن تكون المساحة عبارة عن قيمة رقمية فقط.',

            // القطاع
            'sector_id.required' => 'يرجى اختيار القطاع (Sector) التابع له هذا الجناح.',
            'sector_id.exists' => 'القطاع المحدد غير موجود في النظام.',

            // القاعة
            'hall_id.required' => 'يرجى اختيار القاعة (Hall) التي يقع فيها هذا الجناح.',
            'hall_id.exists' => 'القاعة المحددة غير موجودة في النظام.',

            // التوفر
            'available.boolean' => 'يجب أن تكون حالة التوفر إما متوفر أو غير متوفر.',
        ];
    }
}
