<?php

namespace App\Http\Requests\Contentmanagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // القسم الرئيسي (Hero)
            'hero_title'                 => 'sometimes|nullable',
            'hero_subtitle'              => 'sometimes|nullable',
            'hero_image'                 => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',

            // قسم عن المعرض والاحصائيات (About & Stats)
            'about_title'                => 'sometimes|nullable',
            'about_content'              => 'sometimes|nullable',
            'about_image'                => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'stats_title'                => 'sometimes|nullable',
            'stats_description'          => 'sometimes|nullable',
            'stats_image'                => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'experience_video_url'       => 'sometimes|nullable|url|max:255',

            // قسم لماذا تشارك؟ (cp_benefits)
            'cp_why_participate_title'   => 'sometimes|nullable',
            'cp_benefits_list'           => 'sometimes|nullable|array',

            // قسم أنواع الأجنحة (cp_booths)
            'cp_booths_selection_title'  => 'sometimes|nullable',
            'cp_booths_list'             => 'sometimes|nullable|array',

            // مخطط أرض المعرض (Fairgrounds Plan)
            'cp_fairgrounds_plan_title'  => 'sometimes|nullable',
            'cp_fairgrounds_plan_image'  => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp,svg|max:20480',

            // شروط وأحكام التسجيل (cp_registration)
            'cp_registration_terms'      => 'sometimes|nullable|array',
        ];
    }

    public function attributes(): array
    {
        return [
            'hero_title'               => 'العنوان الرئيسي',
            'hero_subtitle'            => 'العنوان الفرعي',
            'hero_image'               => 'الصورة الرئيسية',
            'about_title'              => 'عنوان من نحن',
            'about_content'            => 'محتوى من نحن',
            'about_image'              => 'صورة من نحن',
            'stats_title'              => 'عنوان الإحصائيات',
            'stats_description'        => 'وصف الإحصائيات',
            'stats_image'              => 'صورة الإحصائيات',
            'experience_video_url'     => 'رابط فيديو التجربة',
            'cp_why_participate_title' => 'عنوان قسم لماذا تشارك',
            'cp_benefits_list'         => 'قائمة الفوائد والمزايا',
            'cp_booths_selection_title'=> 'عنوان قسم أنواع الأجنحة',
            'cp_booths_list'           => 'قائمة أنواع الأجنحة',
            'cp_fairgrounds_plan_title'=> 'عنوان مخطط أرض المعرض',
            'cp_fairgrounds_plan_image'=> 'صورة مخطط أرض المعرض',
            'cp_registration_terms'    => 'شروط وأحكام التسجيل',
        ];
    }
}
