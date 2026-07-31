<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'slot_id'              => 'required|integer|exists:event_slots,id',
            'sector_id'            => 'required|integer|exists:sectors,id',
            'organizer_name'       => 'required|string|max:255',
            'organizer_email'      => 'required|email|max:255',
            'organizer_phone'      => 'required|string|max:50',
            'event_title'          => 'required|string|max:255',
            'event_description'    => 'required|string',
            'Expected_attendance'  => 'required|integer|min:1',
            'equipment_needed'     => 'nullable|string',
            'image'                => 'nullable|file|image|mimes:jpeg,png,jpg|max:10240', // حد أقصى 10 ميغا
       ];
    }
}
