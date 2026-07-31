<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
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
        $userId = auth()->id();
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $userId,
            'phonenumber' => 'sometimes|required|string|unique:users,phonenumber,' . $userId,
            'bio' => 'sometimes|required|string',
            'address' => 'sometimes|required|string',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
