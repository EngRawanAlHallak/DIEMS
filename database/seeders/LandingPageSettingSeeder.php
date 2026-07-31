<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Globalsetting;
use Illuminate\Support\Facades\Cache;


class LandingPageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // --- Section: Hero ---
            [
                'key' => 'hero_title',
                'value' => [
                    'ar' => 'وعز الشرق أوله دمشق',
                    'en' => 'The Glory of the East begins in Damascus'
                ],
                'type' => 'text',
                'group' => 'hero'
            ],
            [
                'key' => 'hero_subtitle',
                'value' => [
                    'ar' => 'معرض دمشق الدولي - منصة الأعمال والتجارة الرائدة في المنطقة',
                    'en' => 'Damascus International Fair - The Leading Business and Trade Platform in the Region'
                ],
                'type' => 'text',
                'group' => 'hero'
            ],
            [
                'key' => 'hero_image',
                'value' => [
                    'ar' => 'main_pages/ZN2DcSVZCG8J95aX8K6aIAjJyVdwiYQuhWTo6mZ9.png',
                    'en' => 'main_pages/ZN2DcSVZCG8J95aX8K6aIAjJyVdwiYQuhWTo6mZ9.png'
                ],
                'type' => 'image',
                'group' => 'hero'
            ],

            // --- Section: About ---
            [
                'key' => 'about_title',
                'value' => [
                    'ar' => 'عن المعرض',
                    'en' => 'About the Fair'
                ],
                'type' => 'text',
                'group' => 'about'
            ],
            [
                'key' => 'about_content',
                'value' => [
                    'ar' => 'معرض دمشق الدولي هو أحد أقدم وأعرق المعارض في المنطقة، يجمع تحت سقف واحد أبرز الشركات والعلامات التجارية من مختلف القطاعات الاقتصادية منذ تأسيسه.',
                    'en' => 'Damascus International Fair is one of the oldest and most prestigious exhibitions in the region, bringing together prominent companies and brands from various economic sectors.'
                ],
                'type' => 'text',
                'group' => 'about'
            ],
            [
                'key' => 'about_image',
                'value' => [
                    'ar' => 'main_pages/9fJ8RLxLCfB7wsZvE9VKQpjCP2j1VFkFYDiulRn7.jpg',
                    'en' => 'main_pages/9fJ8RLxLCfB7wsZvE9VKQpjCP2j1VFkFYDiulRn7.jpg'
                ],
                'type' => 'image',
                'group' => 'about'
            ],

            // --- Section: Stats ---
            [
                'key' => 'stats_title',
                'value' => [
                    'ar' => 'معرض دمشق الدولي 62',
                    'en' => 'Damascus International Fair 62'
                ],
                'type' => 'text',
                'group' => 'stats'
            ],
            [
                'key' => 'stats_description',
                'value' => [
                    'ar' => 'تستضيف الدورة الثانية والستون من معرض دمشق الدولي أكثر من 1,500 عارض من مختلف أنحاء العالم، حيث يتم عرض أحدث المنتجات والخدمات في قطاعات الصناعة، التجارة، التكنولوجيا، والزراعة.',
                    'en' => 'The 62nd edition of the Damascus International Fair hosts more than 1,500 exhibitors from around the world, showcasing the latest products and services across the industrial, commercial, technological, and agricultural sectors.'
                ],
                'type' => 'text',
                'group' => 'stats'
            ],

            [
                'key' => 'stats_image',
                'value' => [
                    'ar' => 'main_pages/k49sh0buhciCH26Qpio29DfjBzRQ1j1ncRqacahQ.jpg',
                    'en'=>'main_pages/k49sh0buhciCH26Qpio29DfjBzRQ1j1ncRqacahQ.jpg'
                ],
                'type' => 'image',
                'group' => 'stats'
            ],



            // --- Section: Video Experience ---

            [
              'key' => 'experience_video_url',
                'value' => [
                    'ar' => 'https://youtu.be/_LdRcEHRKhY?si=wgQzM0MbO1NTF_jn',
                    'en' => 'https://youtu.be/_LdRcEHRKhY?si=wgQzM0MbO1NTF_jn'
                ],
                'type' => 'vedio',
                'group' => 'experience'
            ],


            // --- Section: Date & Location ---
            [
                'key' => 'event_date',
                'value' => [
                    'ar' => '27 آب - 5 أيلول 2025',
                    'en' => 'August 27 - September 5, 2025'
                ],
                'type' => 'text',
                'group' => 'event_info'
            ],
            [
                'key' => 'event_location',
                'value' => [
                    'ar' => 'أرض المعارض، دمشق',
                    'en' => 'Fairgrounds, Damascus'
                ],
                'type' => 'text',
                'group' => 'event_info'
            ],
        ];

        foreach ($settings as $setting) {
            $item = Globalsetting::firstOrNew(['key' => $setting['key']]);

            $item->type = $setting['type'];
            $item->group = $setting['group'];

            // نمرر المصفوفة كاملة ['ar' => '...', 'en' => '...'] دفعة واحدة
            $item->setTranslations('value', $setting['value']);

            $item->save();
        }

        Cache::forget('global_settings_ar');
        Cache::forget('global_settings_en');
    }
}
