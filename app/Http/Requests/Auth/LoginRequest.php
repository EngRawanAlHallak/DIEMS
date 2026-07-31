<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
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
            'identifier'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
             // 3. تعديل شرط الـ fcm_token ديناميكياً ليكون مطلوبا من الادمن فقط
            'fcm_token'  => [
                Rule::requiredIf(function () {
                    $user = User::where('name', $this->identifier)->first();
                    return $user && $user->hasRole('admin');
                }),
                'nullable', // يكون اختيارياً للمستخدمين العاديين
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'Please provide your email or username.',
            'login.string'      => 'The login identifier must be a valid string.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'fcm_token.required'  => 'The FCM token is required for admin accounts.',
        ];
    }
}
