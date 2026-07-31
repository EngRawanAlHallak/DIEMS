<?php

namespace App\Services;

class CompanyRequestService
{
    /**
     * جلب كافة البيانات اللازمة لبناء فورم التسجيل في الفرونت إند مترجمة ديناميكياً.
     */
    public function getRegistrationFormData(): array
    {
        $lang = app()->getLocale(); // 'ar' أو 'en'

        return [
            'form_fields' => [
                [
                    'name' => 'foreign_local',
                    'type' => 'select',
                    'label' => $lang === 'ar' ? 'نوع الشركة' : 'Company Type',
                    'options' => [
                        'local' => $lang === 'ar' ? 'محلي' : 'Local',
                        'foreign' => $lang === 'ar' ? 'أجنبي' : 'Foreign'
                    ]
                ],
                ['name' => 'company_name', 'type' => 'text', 'label' => $lang === 'ar' ? 'اسم الشركة' : 'Company Name'],
                ['name' => 'responsible_name', 'type' => 'text', 'label' => $lang === 'ar' ? 'اسم الشخص المسؤول' : 'Responsible Person Name'],
                ['name' => 'job_title', 'type' => 'text', 'label' => $lang === 'ar' ? 'المسمى الوظيفي' : 'Job Title'],
                ['name' => 'email', 'type' => 'email', 'label' => $lang === 'ar' ? 'البريد الإلكتروني' : 'Email Address'],
                ['name' => 'phone', 'type' => 'tel', 'label' => $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'],
                ['name' => 'nationality', 'type' => 'text', 'label' => $lang === 'ar' ? 'الجنسية' : 'Nationality'],
                ['name' => 'commercial_register', 'type' => 'text', 'label' => $lang === 'ar' ? 'السجل التجاري' : 'Commercial Register'],
                ['name' => 'address', 'type' => 'textarea', 'label' => $lang === 'ar' ? 'العنوان التفصيلي' : 'Detailed Address'],
                ['name' => 'sector', 'type' => 'select', 'label' => $lang === 'ar' ? 'القطاع' : 'Sector', 'options' => $this->getSectorOptions($lang)],
                ['name' => 'company_description', 'type' => 'textarea', 'label' => $lang === 'ar' ? 'وصف الشركة' : 'Company Description'],
                ['name' => 'requested_area', 'type' => 'number', 'label' => $lang === 'ar' ? 'المساحة المطلوبة (م2)' : 'Requested Area (m2)'],
                ['name' => 'setup_preference', 'type' => 'select', 'label' => $lang === 'ar' ? 'تفضيلات التجهيز' : 'Setup Preference', 'options' => $this->getSetupOptions($lang)],

                [
                    'name' => 'documents',
                    'type' => 'file_uploader',
                    'label' => $lang === 'ar' ? 'وثائق الشركة وعقودها' : 'Company Documents & Contracts',
                    'document_types' => $this->getDocumentTypes($lang)
                ],
            ],
        ];
    }

    /**
     * جلب خيارات أنواع الوثائق المتاحة مترجمة
     */
    private function getDocumentTypes(string $lang): array
    {
        return [
            ['label' => $lang === 'ar' ? 'سجل تجاري' : 'Commercial Register', 'value' => 'commercial_register'],
            ['label' => $lang === 'ar' ? 'هوية وطنية' : 'National ID', 'value' => 'national_id'],
            ['label' => $lang === 'ar' ? 'رخصة مزاولة النشاط' : 'Business License', 'value' => 'business_license'],
            ['label' => $lang === 'ar' ? 'وثيقة أخرى' : 'Other Document', 'value' => 'other_document'],
        ];
    }

    /**
     * جلب خيارات التجهيز المتاحة مترجمة
     */
    private function getSetupOptions(string $lang): array
    {
        return [
            ['label' => $lang === 'ar' ? 'جناح مجهز' : 'Equipped Booth', 'value' => 'Equipped Booth'],
            ['label' => $lang === 'ar' ? 'جناح غير مجهز' : 'Not Equipped Booth', 'value' => 'Not Equipped Booth'],
            ['label' => $lang === 'ar' ? 'مساحة فقط' : 'Row Space Only', 'value' => 'Row Space Only'],
            ['label' => $lang === 'ar' ? 'أكشاك بيع ab' : 'Kiosk AB', 'value' => 'Kiosk AB'],
            ['label' => $lang === 'ar' ? 'أكشاك بيع cd' : 'Kiosk CD', 'value' => 'Kiosk CD'],
        ];
    }

    /**
     * جلب خيارات القطاعات مترجمة ديناميكياً
     */
    private function getSectorOptions(string $lang): array
    {
        return [
            ['label' => $lang === 'ar' ? 'الصناعات الغذائية' : 'Food Industries', 'value' => 'Food Industries'],
            ['label' => $lang === 'ar' ? 'الثقافة والفنون' : 'Culture & Arts', 'value' => 'Culture & Arts'],
            ['label' => $lang === 'ar' ? 'الحرف اليدوية' : 'Handicrafts', 'value' => 'Handicrafts'],
            ['label' => $lang === 'ar' ? 'الصناعة والتقنية' : 'Industry & Technology', 'value' => 'Industry & Technology'],
            ['label' => $lang === 'ar' ? 'الأنشطة الرياضية' : 'Sports Activities', 'value' => 'Sports Activities'],
            ['label' => $lang === 'ar' ? 'التعليم والبرامج' : 'Education & Programs', 'value' => 'Education & Programs'],
            ['label' => $lang === 'ar' ? 'الصحة والجمال' : 'Health & Beauty', 'value' => 'Health & Beauty'],
            ['label' => $lang === 'ar' ? 'البناء والعقارات' : 'Construction & Real Estate', 'value' => 'Construction & Real Estate'],
            ['label' => $lang === 'ar' ? 'الطاقة المتجددة' : 'Renewable Energy', 'value' => 'Renewable Energy'],
            ['label' => $lang === 'ar' ? 'السيارات والمركبات' : 'Automobiles & Vehicles', 'value' => 'Automobiles & Vehicles'],
            ['label' => $lang === 'ar' ? 'الموضة والأزياء' : 'Fashion & Clothing', 'value' => 'Fashion & Clothing'],
            ['label' => $lang === 'ar' ? 'الإعلام والاتصالات' : 'Media & Communications', 'value' => 'Media & Communications'],
        ];
    }
}
