<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
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
            'foreign_local'        => 'required|in:foreign,local',
            'company_name'         => 'required|string|max:255',
            'responsible_name'     => 'required|string|max:255',
            'job_title'            => 'required|string',
            'email'                => 'required|email|unique:company_requests,email',
            'phone'                => 'required|string',
            'nationality'          => 'required|string',
            'commercial_register'  => 'required|string',
            'address'              => 'required|string',
            'sector'               => 'required|string',
            'company_description'  => 'required|string',
            'requested_area'       => 'required|numeric|min:1',
            'setup_preference'     => 'required|in:Equipped Booth,Not Equipped Booth,Row Space Only,Kiosk AB,Kiosk CD',
            'terms_accepted'       => 'accepted', // التأكد من ضغط checkbox الشروط
        ];
    }
}
