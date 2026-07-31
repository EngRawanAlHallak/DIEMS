<?php

namespace Database\Seeders;

use App\Models\ExhibitionProfile;
use App\Models\GlobalSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WelcomePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExhibitionProfile::updateOrCreate(
        // 1. معيار البحث (نبحث عن السجل بواسطة الدورة مثلاً لعدم التكرار)
            ['session' => '62'],

            // 2. البيانات التي سيتم إدخالها أو تحديثها
            [
                'name' => [
                    'ar' => 'معرض دمشق الدولي',
                    'en' => 'Damascus International Fair'
                ],
                'address' => [
                    'ar' => 'دمشق - طريق المطار - مدينة المعارض',
                    'en' => 'Damascus - Airport Road - Fairground'
                ],
                'bio' => [
                    'ar' => 'يعد معرض دمشق الدولي من أعرق التظاهرات الاقتصادية والاجتماعية في المنطقة.',
                    'en' => 'Damascus International Fair is one of the oldest economic and social events in the region.'
                ],
                'start_date'     => '2026-08-20',
                'end_date'       => '2026-08-30',
                'open_time'      => '16:00:00',
                'close_time'     => '22:00:00',
                'contact_email'  => 'info@damascus-fair.gov.sy',
                'contact_phone'  => '+963111234567',
                'emergency_phone'=> '+963119876',
                'instagram_url'  => 'https://instagram.com/dif_syria',
                'facebook_url'   => 'https://facebook.com/dif_syria',
                'x_url'          => 'https://x.com/dif_syria',
                'title'          => 'MY SYRIA',
                'syria_logo'     => 'logos/syria_official_logo.png',
                'welcome_video'  => 'videos/welcome_2026.mp4',
                'transport_interval_minutes' => 30,
                'transport_start_time' => '15:00:00',
                'transport_end_time' => '23:00:00',
            ]
        );
    }
}
