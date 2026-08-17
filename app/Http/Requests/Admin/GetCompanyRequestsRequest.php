<?php

namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class GetCompanyRequestsRequest extends FormRequest
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
            'request_status' => ['nullable','in:all,pending,approved,rejected,expired,action_required'],
            'payment_status' => ['nullable','in:all,paid,unpaid,partial_paid'],
            'date'           => ['nullable','date_format:Y-m-d'],
            'search'         => ['nullable','string','max:255'],
            'page'           => ['nullable','integer','min:1'],
        ];
    }

}
