<?php

namespace Database\Seeders;

use App\Models\GateCode;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ar_SA'); // لدعم الأسماء العربية الواقعية

        // -------------------------------------------------------------
        // 1. إنشاء أنواع التذاكر الأساسية المعرض
        // -------------------------------------------------------------
        $types = [
            [
                'name' => ['ar' => 'تذكرة عادية - فردية', 'en' => 'Regular ticket - single'],
                'description' => ['ar' => 'تسمح بدخول شخص واحد فقط إلى المعرض لمرة واحدة.', 'en' => 'Only one person is allowed entry to the exhibition once.'],
                'price' => 50.00,
                'persons_count' => 1,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تذكرة VIP', 'en' => 'VIP Ticket'],
                'description' => ['ar' => 'تسمح بدخول شخص واحد مع ميزة الدخول إلى قاعة كبار الشخصيات وحضور الندوات.', 'en' => 'It allows entry for one person with the privilege of accessing the VIP lounge and attending seminars.'],
                'price' => 150.00,
                'persons_count' => 1,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تذكرة مجموعات / عائلية', 'en' => 'Group/Family Ticket'],
                'description' => ['ar' => 'تذكرة مخفضة صالحة لدخول 4 أشخاص معاً.', 'en' => 'A discounted ticket valid for entry for 4 people together.'],
                'price' => 160.00, // سعر مخفض للمجموعات
                'persons_count' => 4,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تذكرة طلابية مخفضة', 'en' => 'Discounted student ticket'],
                'description' => ['ar' => 'تذكرة مخصصة للطلاب بموجب البطاقة الجامعية.', 'en' => 'A ticket reserved for students with a university ID card.'],
                'price' => 25.00,
                'persons_count' => 1,
                'is_active' => false, // مغلقة مؤقتاً للتجربة
            ]
        ];

        $createdTypes = [];
        foreach ($types as $type) {
            // 🚀 التحديث الصحيح: البارامتر الأول هو حقل البحث داخل الـ JSON والبارامتر الثاني هو المصفوفة كاملة
            $createdTypes[] = TicketType::updateOrCreate(
                ['name->en' => $type['name']['en']],
                $type
            );
        }

        // -------------------------------------------------------------
        // 2. إنشاء أكواد البوابة اليومية (لليوم وللأيام السابقة)
        // -------------------------------------------------------------
        // كود اليوم الفعال
        GateCode::Create(
            //['valid_for_date' => today()->toDateString()], // 🚀 الحل هنا
            [
                'code' => strtoupper(Str::random(10)),
                'valid_for_date' => Carbon::today()->toDateString(),
                'starts_at' => '09:00:00',
                'ends_at' => '21:00:00',
                'is_active' => false,
            ]
        );

// كود الأمس (غير فعال)
        GateCode::Create(
            //['valid_for_date' => Carbon::yesterday()->toDateString()], // 🚀 والحل هنا أيضاً
            [
                'code' => strtoupper(Str::random(10)),
                'valid_for_date' => Carbon::yesterday()->toDateString(),
                'starts_at' => '09:00:00',
                'ends_at' => '21:00:00',
                'is_active' => false,
            ]
        );


        // -------------------------------------------------------------
        // 3. إنشاء طلبات وتذاكر تجريبية (Orders & Tickets)
        // -------------------------------------------------------------
        $paymentStatuses = ['paid', 'pending', 'failed'];
        $interests = ['تكنولوجيا', 'طاقة متجددة', 'صناعات غذائية', 'ذكاء اصطناعي', 'تسويق وتجارة'];

        // سنقوم بإنشاء 30 طلب عشوائي لتغذية قاعدة البيانات
        for ($i = 0; $i < 30; $i++) {

            // اختيار نوع تذكرة عشوائي من الأنواع الفعالة
            $ticketType = $faker->randomElement(array_filter($createdTypes, fn($t) => $t->is_active));
            $paymentStatus = $faker->randomElement($paymentStatuses);

            // حساب السعر الإجمالي بناءً على سعر نوع التذكرة
            $totalAmount = $ticketType->price;

            // تحديد تاريخ الإنشاء (بعضها اليوم والبعض الآخر خلال الأسبوع الماضي لتبدو الإحصائيات ممتازة)
            $createdAt = $faker->dateTimeBetween('-5 days', 'now');

            // إنشاء الطلب
            $order = TicketOrder::updateOrCreate([
                'uuid' => (string)Str::uuid(),
                'guest_id' => 'guest_' . Str::random(20), // محاكاة الـ Device ID من الـ Local Storage
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'expires_at' => $paymentStatus === 'pending' ? Carbon::parse($createdAt)->addMinutes(15) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // إذا كان الطلب مدفوعاً، نقوم بإنشاء التذاكر الفعلية التابعة له بناءً على عدد الأفراد المسموح
            if ($paymentStatus === 'paid') {
                for ($j = 0; $j < $ticketType->persons_count; $j++) {

                    $ticketStatus = $faker->randomElement(['valid', 'used']);
                    $isUsed = $ticketStatus === 'used';

                    Ticket::updateOrCreate([
                        'uuid' => (string)Str::uuid(), // هذا الـ UUID سيستخدمه الفرونت لتوليد الـ QR
                        'ticket_order_id' => $order->id,
                        'ticket_type_id' => $ticketType->id,
                        'visitor_name' => $faker->name, // الشخص الأول هو المشتري، والباقي مرافقين
                        'visitor_email' =>$faker->safeEmail,
                        'visitor_phone' => $faker->phoneNumber,
                        'interest_field' => $faker->randomElement($interests),
                        'status' => $ticketStatus,
                        'used_at' => $isUsed ? $faker->dateTimeBetween($createdAt, 'now') : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }
    }
}
