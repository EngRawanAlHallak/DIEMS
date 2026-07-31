<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // استخدام 'sometimes' يعني: "طبّق القاعدة فقط إذا كان الحقل موجوداً في الطلب"
        return [
            'foreign_local'        => 'sometimes|in:foreign,local',
            'company_name'         => 'sometimes|string|max:255',
            'responsible_name'     => 'sometimes|string|max:255',
            'job_title'            => 'sometimes|string',
            'phone'                => 'sometimes|string',
            'nationality'          => 'sometimes|string',
            'commercial_register'  => 'sometimes|string',
            'address'              => 'sometimes|string',
            'sector'               => 'sometimes|string',
            'company_description'  => 'sometimes|string',
            'requested_area'       => 'sometimes|numeric|min:1',
            'setup_preference'     => 'sometimes|in:Equipped Booth,Not Equipped Booth,Row Space Only,Kiosk AB,Kiosk CD',

            // تحقق بسيط من مصفوفة الملفات (إن تم إرسالها)
            'documents'            => 'sometimes|array',
            'documents.*.file'     => 'required_with:documents|file|mimes:pdf,jpg,png|max:2048',
            'documents.*.type'     => 'required_with:documents|string'
        ];
    }
}
