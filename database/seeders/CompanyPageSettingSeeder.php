<?php

namespace Database\Seeders;

use App\Models\Globalsetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;


class CompanyPageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $settings = [
            // --- قسم: لماذا تشارك؟ (cp_benefits) ---
            [
                'key' => 'cp_why_participate_title',
                'value' => [
                    'ar' => 'لماذا تشارك؟',
                    'en' => 'Why Participate?'
                ],
                'type' => 'text',
                'group' => 'cp_benefits'
            ],
            [
                'key' => 'cp_benefits_list',
                'value' => [
                    'ar' => [
                        ['title' => 'تواصل مباشر', 'desc' => 'تفاعل مع عملائك وبناء علاقات تجارية قوية ومستدامة.', 'icon' => 'direct_contact'],
                        ['title' => 'وصول واسع', 'desc' => 'فرصة للوصول إلى أكثر من 100,000 زائر من مختلف القطاعات.', 'icon' => 'wide_reach'],
                        ['title' => 'فرص تجارية', 'desc' => 'استكشف آفاقاً جديدة واعقد صفقات تجارية استراتيجية.', 'icon' => 'business_opportunities']
                    ],
                    'en' => [
                        ['title' => 'Direct Contact', 'desc' => 'Interact with your customers and build strong, sustainable business relationships.', 'icon' => 'direct_contact'],
                        ['title' => 'Wide Reach', 'desc' => 'An opportunity to reach over 100,000 visitors from various sectors.', 'icon' => 'wide_reach'],
                        ['title' => 'Business Opportunities', 'desc' => 'Explore new horizons and conclude strategic business deals.', 'icon' => 'business_opportunities']
                    ]
                ],
                'type' => 'json',
                'group' => 'cp_benefits'
            ],

            // --- قسم: أنواع الأجنحة (cp_booths) ---
            [
                'key' => 'cp_booths_selection_title',
                'value' => [
                    'ar' => 'اختر نوع الجناح المناسب',
                    'en' => 'Choose the Appropriate Booth Type'
                ],
                'type' => 'text',
                'group' => 'cp_booths'
            ],
            [
                'key' => 'cp_booths_list',
                'value' => [
                    'ar' => [
                        [
                            'id' => 'space_only',
                            'title' => 'المساحة فقط',
                            'desc' => 'يتم توفير مساحة الأرضية فقط، وأنت مسؤول عن تصميم وبناء جناحك وفقاً للوائح.',
                            'features' => ['مساحة مفتوحة', 'مرونة كاملة في التصميم']
                        ],
                        [
                            'id' => 'unequipped_booth',
                            'title' => 'جناح غير مجهز',
                            'desc' => 'الخيار الأمثل للعلامات التي تبحث عن الظهور الاحترافي الكامل مع خيارات أثاث متنوعة.',
                            'features' => ['جدران وحدود محمية', 'إضاءة أساسية', 'اسم الشركة']
                        ],
                        [
                            'id' => 'equipped_booth',
                            'title' => 'جناح مجهز',
                            'desc' => 'خدمة بناء أجنحة مجهزة مصممة خصيصاً لكم لتقديم تجربة متكاملة وأسعار تنافسية.',
                            'features' => ['تصميم وديكور شامل', 'أثاث فاخر', 'خدمات ضيافة']
                        ]
                    ],
                    'en' => [
                        [
                            'id' => 'space_only',
                            'title' => 'Space Only',
                            'desc' => 'Only floor space is provided, and you are responsible for the design and construction of your booth according to regulations.',
                            'features' => ['Open space', 'Full design flexibility']
                        ],
                        [
                            'id' => 'unequipped_booth',
                            'title' => 'Unequipped Booth',
                            'desc' => 'The ideal choice for brands looking for full professional appearance with various furniture options.',
                            'features' => ['Protected walls and boundaries', 'Basic lighting', 'Company name']
                        ],
                        [
                            'id' => 'equipped_booth',
                            'title' => 'Equipped Booth',
                            'desc' => 'A custom-designed equipped booth building service to provide you with an integrated experience and competitive prices.',
                            'features' => ['Comprehensive design and decor', 'Luxury furniture', 'Hospitality services']
                        ]
                    ]
                ],
                'type' => 'json',
                'group' => 'cp_booths'
            ],

            // --- قسم: خريطة المعرض (cp_map) ---
            [
                'key' => 'cp_fairgrounds_plan_title',
                'value' => [
                    'ar' => 'خريطة المعرض',
                    'en' => 'Fairgrounds Plan'
                ],
                'type' => 'text',
                'group' => 'cp_map'
            ],
            [
                'key' => 'cp_fairgrounds_plan_image',
                'value' => [
                    'ar' => 'main_pages/7mpHeonWCGRyiJizSyqBvGdJbQpTtDDQaBLX0ZYd.jpg',
                    'en' => 'main_pages/7mpHeonWCGRyiJizSyqBvGdJbQpTtDDQaBLX0ZYd.jpg'
                ],
                'type' => 'image',
                'group' => 'cp_map'
            ],

            // --- قسم الشروط والأحكام (cp_registration) ---
            [
                'key' => 'cp_registration_terms',
                'value' => [
                    'ar' => [
                        'التقيّد بمنع تثقيب الأرضيات أو الجدران أو تركيب تجهيزات وديكورات وتثبيتها في الأرض، والاستعاضة عنها بأساليب لا تضر بالمنشأة.',
                        'التقيّد بعدم إدخال السيارات لأي سبب إلى داخل الأجنحة.',
                        'عدم دخول السيارات السياحية لحرم المدينة خلال فترة المعرض (البوابة 13) وركنها في المرائب المخصصة.',
                        'يسمح بدخول سيارات النقل المحملة بالبضائع في الأوقات المحددة مسبقاً من قبل الإدارة.',
                        'عدم منح بطاقات دخول سيارات إلى المدينة من قبل المشاركين.',
                        'التقيّد بالشاخصات المرورية وبوابات الدخول والخروج المخصصة.',
                        'عدم استخدام الرصيف كمرآب لركن السيارات.',
                        'إعلام المؤسسة مسبقاً وبشكل خطي في حال التعاقد مع شركة حماية أمنية.',
                        'توصيل الكهرباء بالتنسيق مع ورشة المؤسسة حصراً، ويمنع التوصيل العشوائي تحت طائلة المساءلة.',
                        'يمنع الصعود لأسطح الأجنحة أو إغلاق أبواب الطوارئ ومداخل الإطفاء أو وضع قواطع تخفي أبواب البوفيهات.',
                        'عدم لصق أي ملصق أو شعار أو تعليق أعلام على الجدران والأسقف إلا بمعرفة الإدارة والورشة المختصة.',
                        'عدم اللمس أو العبث بشبكة الألياف الضوئية أو أي من ملحقاتها منعاً باتاً.',
                        'يمنع استخدام المواد والأجهزة المحرقة (ملاحم، سخانات، دخان وأراكيل) داخل الأجنحة بشكل قطعي.',
                        'عدم القيام بأعمال البخ إطلاقاً داخل الأجنحة، ويمكن استخدام الدهان عند الضرورة.',
                        'المحافظة على نظافة المدينة وأجنحتها ورمي المخلفات في الأماكن المخصصة.',
                        'التواصل المباشر مع مديرية شؤون مدينة المعارض عند حدوث أي طارئ.',
                        'التقيّد بأوقات الزيارة وإغلاق الأجنحة فور انتهائها. فترة التجهيز من 8 صباحاً حتى 10 مساءً.',
                        'يجب دفع رسوم 25% كدفعة أولية خلال مهلة أقصاها 48 ساعة من تاريخ الطلب، وفي حال التخلف سيتوجب على الشركة إعادة تعبئة البيانات وإرسال الطلب مرة أخرى.',
                        'يجب حجز 12 متر كحد أدنى للمبنى و50 متر للمساحة فقط.'
                    ],
                    'en' => [
                        'Adhere to the prohibition of drilling floors or walls or installing fixed decorations; use methods that do not damage the facility.',
                        'Cars are strictly prohibited from entering the booths for any reason.',
                        'Private cars are not allowed inside the city premises during the fair (Gate 13) and must be parked in designated lots.',
                        'Freight trucks loaded with goods are allowed only at times pre-specified by the management.',
                        'Participants are not allowed to issue car entry permits into the city.',
                        'Compliance with traffic signs and designated entry and exit gates is mandatory.',
                        'Using the sidewalk as a parking area for cars is prohibited.',
                        'The institution must be notified in writing in advance if contracting with a security protection company.',
                        'Electrical connection must be coordinated exclusively with the institution\'s workshop; random connection is prohibited.',
                        'Climbing on booth roofs, closing emergency exits, or placing partitions that hide buffet doors is prohibited.',
                        'Do not stick posters, logos, or hang flags on walls and ceilings without the approval of the management.',
                        'Touching or tampering with the fiber optic network or its accessories is strictly prohibited.',
                        'The use of combustible materials and devices (welders, heaters, smoke, and hookahs) inside booths is strictly forbidden.',
                        'Spray painting is strictly prohibited inside the booths; traditional paint can be used if necessary.',
                        'Maintain the cleanliness of the city and its booths and dispose of waste in designated areas.',
                        'Direct contact with the Fair City Affairs Directorate is required in case of any emergency.',
                        'Adhere to visiting hours and close booths immediately after they end. Preparation period is from 8 AM to 10 PM.',
                        'A 25% down payment must be paid within a maximum of 48 hours of the request; otherwise, the company must re-apply.',
                        'A minimum of 12 meters must be booked for the building and 50 meters for space-only options.'
                    ]
                ],
                'type' => 'json',
                'group' => 'cp_registration'
            ]
        ];

        foreach ($settings as $setting) {
            $item = Globalsetting::firstOrNew(['key' => $setting['key']]);

            $item->type = $setting['type'];
            $item->group = $setting['group'];

            // نمرر المصفوفة كاملة ['ar' => '...', 'en' => '...'] دفعة واحدة
            $item->setTranslations('value', $setting['value']);

            $item->save();
        }

        // مسح الكاش لضمان تحديث اللغات
        Cache::forget('global_settings_ar');
        Cache::forget('global_settings_en');
    }
}
