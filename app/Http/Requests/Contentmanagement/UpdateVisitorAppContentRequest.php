<?php

namespace App\Http\Requests\Contentmanagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitorAppContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => 'sometimes|required|string|max:255',
            'syria_logo'    => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120', // الحد الأقصى 5 ميجابايت
            'welcome_video' => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200',  // الحد الأقصى 50 ميجابايت
        ];
    }

    public function attributes(): array
    {
        return [
            'title'         => 'عنوان الترحيب',
            'syria_logo'    => 'شعار سوريا',
            'welcome_video' => 'فيديو الترحيب',
        ];
    }
}
