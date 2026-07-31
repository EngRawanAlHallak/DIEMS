<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BookTicketRequest extends FormRequest
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
            'guest_id'                 => ['nullable','string'],
            'ticket_type_id'           => ['required','exists:ticket_types,id'],
            'visitors'                 => ['required','array','min:1'],
            'visitors.*.name'          => ['required','string'],
            'visitors.*.email'         => ['required','email'],
            'visitors.*.phone'         => ['required','string','size:10'],
            'visitors.*.interest_field'=> ['required','string'],
        ];
    }

}

