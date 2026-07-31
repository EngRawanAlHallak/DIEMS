<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddSlotRequest extends FormRequest
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
            'slot_date'  => ['required','date','date_format:Y-m-d'],
            'start_time' => ['required','date_format:g:i A'],
            'end_time'   => ['required','date_format:g:i A','after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'slot_date.required'     => 'تاريخ الفترة الزمنية مطلوب ولا يمكن تركه فارغاً.',
            'slot_date.date'         => 'الرجاء إدخال تاريخ صحيح.',
            'slot_date.date_format'  => 'يجب أن يكون التاريخ بصيغة سريعة وصحيحة مثل: Y-m-d (مثال: 2026-08-01).',

            // رسائل التحقق لوقت البدء
            'start_time.required'    => 'وقت بدء الفترة الزمنية مطلوب.',
            'start_time.date_format' => 'يجب أن يكون وقت البدء بصيغة 12 ساعة مع تحديد AM أو PM (مثال: 10:30 AM).',

            // رسائل التحقق لوقت النهاية
            'end_time.required'      => 'وقت نهاية الفترة الزمنية مطلوب.',
            'end_time.date_format'   => 'يجب أن يكون وقت النهاية بصيغة 12 ساعة مع تحديد AM أو PM (مثال: 12:30 PM).',
            'end_time.after'         => 'تنبيه: يجب أن يكون وقت النهاية بعد وقت البدء منطقياً.',
        ];
    }
}
