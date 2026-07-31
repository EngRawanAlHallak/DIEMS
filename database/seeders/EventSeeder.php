<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\EventRequest;
use App\Models\EventSlot;
use App\Models\Hall;
use App\Models\Promotion;
use App\Models\Sector;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fairStart = Carbon::create(2026, 8, 1);

        $timeSlots = [
            ['start' => '10:00:00', 'end' => '12:00:00'],
            ['start' => '13:00:00', 'end' => '15:00:00'],
            ['start' => '17:00:00', 'end' => '19:00:00'],
            ['start' => '20:00:00', 'end' => '22:00:00'],
        ];

        // 10 أيام من المعرض
        for ($day = 0; $day < 10; $day++) {
            $date = $fairStart->copy()->addDays($day)->toDateString();

            foreach ($timeSlots as $slot) {
                EventSlot::firstOrCreate(
                    [
                        'slot_date'  => $date,
                        'start_time' => $slot['start'],
                    ],
                    [
                        'slot_date'  => $date,
                        'start_time' => $slot['start'],
                        'end_time'   => $slot['end'],
                        'available'  => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }


        $slots   = EventSlot::all();

        $sectors = Sector::all()->mapWithKeys(function ($item) {
            return [$item->getTranslation('name', 'ar') => $item];
        });

        $halls = Hall::all()->mapWithKeys(function ($item) {
            return [$item->getTranslation('name', 'ar') => $item];
        });

        // دالة مساعدة للبحث عن السلوت المناسب من المصفوفة المجلوبة
        $getSlotId = function ($date, $time) use ($slots) {
            return $slots->where('slot_date', $date)
                ->where('start_time', $time)
                ->first()?->id;
        };

        $events = [
            [
                'date'                => '2026-08-01',
                'time'                => '10:00:00',
                'sector_name'         => 'التعليم والبرامج',
                'hall_name'           => 'قاعة الثقافة',
                'organizer_name'      => 'أحمد الفاروق',
                'organizer_email'     => 'info@deenandwatan.sy',
                'organizer_phone'     => '+963 11 789 0123',
                'event_title'         => ['ar' => 'دين وطن — الحلقة الافتتاحية', 'en' => 'Deen & Watan — Opening Episode'],
                'event_description'   => ['ar' => 'برنامج دين وطن يستضيف نخبة من العلماء لمناقشة القضايا المعاصرة.', 'en' => 'Deen & Watan program hosts elite scholars to discuss contemporary issues.'],
                'Expected_attendance' => 500,
                'equipment_needed'    => 'شاشة عرض كبيرة، منصة تحدث',
                'status'              => 'approved',
                'is_special'          => true,
            ],
            [
                'date'                => '2026-08-01',
                'time'                => '13:00:00',
                'sector_name'         => 'التعليم والبرامج',
                'hall_name'           => 'قاعة الثقافة',
                'organizer_name'      => 'أحمد الفاروق',
                'organizer_email'     => 'info@deenandwatan.sy',
                'organizer_phone'     => '+963 11 789 0123',
                'event_title'         => ['ar' => 'دين وطن — الحلقة الافتتاحية', 'en' => 'Deen & Watan — Opening Episode'],
                'event_description'   => ['ar' => 'برنامج دين وطن يستضيف نخبة من العلماء لمناقشة القضايا المعاصرة.', 'en' => 'Deen & Watan program hosts elite scholars to discuss contemporary issues.'],
                'Expected_attendance' => 500,
                'equipment_needed'    => 'شاشة عرض كبيرة، منصة تحدث',
                'status'              => 'paid',
                'is_special'          => true,
            ],
            // ... تكرار نفس التنسيق لبقية حلقات "دين وطن" في نفس اليوم ...
            [
                'date'                => '2026-08-02',
                'time'                => '10:00:00',
                'sector_name'         => 'الثقافة والفنون',
                'hall_name'           => 'قاعة الثقافة',
                'organizer_name'      => 'ليلى الخوري',
                'organizer_email'     => 'events@hikayat.sy',
                'organizer_phone'     => '+963 11 678 9012',
                'event_title'         => ['ar' => 'حكاية المعارف — من دمشق إلى العالم', 'en' => 'Tales of Knowledge — From Damascus to the World'],
                'event_description'   => ['ar' => 'رحلة ثقافية في حضارة دمشق عبر الفنون التقليدية.', 'en' => 'A cultural journey through the civilization of Damascus via traditional arts.'],
                'Expected_attendance' => 300,
                'equipment_needed'    => 'مسرح صغير، إضاءة، نظام صوت',
                'status'              => 'approved',
                'is_special'          => true,
            ],
            [
                'date'                => '2026-08-03',
                'time'                => '10:00:00',
                'sector_name'         => 'الصناعة والتقنية',
                'hall_name'           => 'قاعة الصناعة والتقنية',
                'organizer_name'      => 'رامي السيد',
                'organizer_email'     => 'tech@advanced-tech.sy',
                'organizer_phone'     => '+963 11 901 2345',
                'event_title'         => ['ar' => 'مستقبل سوريا التقني — رؤية 2030', 'en' => "Syria's Tech Future — Vision 2030"],
                'event_description'   => ['ar' => 'محاضرة تستعرض خارطة طريق التحول الرقمي في سوريا.', 'en' => 'A lecture showcasing the digital transformation roadmap in Syria.'],
                'Expected_attendance' => 300,
                'equipment_needed'    => 'شاشة عرض، حاسوب، نظام صوت',
                'status'              => 'rejected',
                'is_special'          => false,
            ],
            [
                'date'                => '2026-08-05',
                'time'                => '13:00:00',
                'sector_name'         => 'البناء والعقارات',
                'hall_name'           => 'قاعة الصناعة والتقنية',
                'organizer_name'      => 'سامر الحسيني',
                'organizer_email'     => 'events@modernbuild.sy',
                'organizer_phone'     => '+963 11 012 3456',
                'event_title'         => ['ar' => 'مستقبل البناء المستدام', 'en' => 'The Future of Sustainable Construction'],
                'event_description'   => ['ar' => 'ندوة تستعرض أحدث تقنيات البناء الصديق للبيئة.', 'en' => 'A seminar reviewing the latest eco-friendly construction technologies.'],
                'Expected_attendance' => 200,
                'equipment_needed'    => 'قاعة مؤتمرات، نماذج معمارية',
                'status'              => 'rejected',
                'is_special'          => false,
            ]
        ];

        foreach ($events as $e) {
            $slotId   = $getSlotId($e['date'], $e['time']);
            $sectorId = $sectors->get($e['sector_name'])?->id;
            $hallId   = $halls->get($e['hall_name'])?->id;

            if ($slotId && $sectorId && $hallId) {
                EventRequest::firstOrCreate(
                    [
                        'slot_id'         => $slotId,
                        'organizer_email' => $e['organizer_email']
                    ],
                    [
                        'sector_id'           => $sectorId,
                        'hall_id'             => $hallId,
                        'organizer_name'      => $e['organizer_name'],
                        'organizer_phone'     => $e['organizer_phone'],
                        'event_title'         => $e['event_title'],
                        'event_description'   => $e['event_description'],
                        'Expected_attendance' => $e['Expected_attendance'],
                        'equipment_needed'    => $e['equipment_needed'],

                        // التعديل هنا: فصل الحالة
                        'request_status'      => $e['status'] === 'paid' ? 'approved' : $e['status'],
                        'payment_status'      => $e['status'] === 'paid' ? 'paid' : 'unpaid',

                        // حقول مالية افتراضية
                        'total_price'         => 1000.00,
                        //'required_deposit'    => 200.00,
                        //'paid_amount'         => $e['status'] === 'paid' ? 1000.00 : 0,
                        'payment_due_date'    => Carbon::parse($e['date'])->subDays(7),

                        'is_special'          => $e['is_special'],
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]
                );

                // تحديث حالة السلوت: نعتبره محجوزاً إذا تمت الموافقة أو الدفع
                if (in_array($e['status'], ['approved', 'paid'])) {
                    EventSlot::where('id', $slotId)->update(['available' => false]);
                }
            } else {
                // سطر برمجي للمساعدة في معرفة الخطأ في التيرمنال
// استبدلي سطر الـ warn القديم بهذا الكود مؤقتاً:
                if (!$slotId) $this->command->error("Missing Slot for: " . $e['date'] . " " . $e['time']);
                if (!$sectorId) $this->command->error("Missing Sector: " . $e['sector_name']);
                if (!$hallId) $this->command->error("Missing Hall: " . $e['hall_name']);
            }
        }


        // جلب الشركات التي لديها منتجات فقط لربط العروض بها
        $companies = Company::has('products')->with('products')->get();

        foreach ($companies as $company) {
            // 1. إنشاء عرض من نوع "خصم مئوي" (Discount)
            $discountPromo = Promotion::create([
                'company_id'          => $company->id,
                'type'                => 'discount',
                'discount_percentage' => rand(10, 50), // خصم عشوائي بين 10 و 50%
                'start_date'          => Carbon::now(),
                'end_date'            => Carbon::now()->addDays(30),
                'is_active'           => true,
            ]);

            // ربط العرض بمنتج واحد أو اثنين من منتجات الشركة
            $productsForDiscount = $company->products->random(min(2, $company->products->count()));
            $discountPromo->products()->attach($productsForDiscount->pluck('id'));


            // 2. إنشاء عرض من نوع "حزمة" (Bundle)
            $bundlePromo = Promotion::create([
                'company_id'          => $company->id,
                'type'                => 'bundle',
                'total_package_price' => 150.00, // سعر الحزمة كاملة
                'start_date'          => Carbon::now(),
                'end_date'            => Carbon::now()->addDays(15),
                'is_active'           => true,
            ]);

            // ربط الحزمة بمنتجات معينة (مثلاً 3 منتجات تشكل حزمة واحدة)
            if ($company->products->count() >= 3) {
                $productsForBundle = $company->products->random(3);
                $bundlePromo->products()->attach($productsForBundle->pluck('id'));
            }
        }
    }
}
