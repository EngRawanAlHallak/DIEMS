<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHallRequest extends FormRequest
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
            'id'             => ['required','exists:halls,id'],
            'name'           => ['sometimes','string'],
            'description'    => ['nullable','string'],
            'floor'          => ['sometimes','string'],
            'total_area_sqm' => ['sometimes','numeric']
        ];
    }
}
