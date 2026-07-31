<?php

namespace Database\Seeders;

use App\Models\Booth;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\CompanyRequest;
use App\Models\Hall;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use App\Models\Sector;
use App\Models\Transportation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        $halls = [
            [
                'name'           => ['ar' => 'قاعة الثقافة', 'en' => 'Culture Hall'],
                'description'    => null,
                'total_area_sqm' => 2500.00,
                'floor'          => 'Ground'
            ],
            [
                'name'           => ['ar' => 'القاعة الرياضية', 'en' => 'Sports Hall'],
                'description'    => null,
                'total_area_sqm' => 3000.00,
                'floor'          => 'Ground'
            ],
            [
                'name'           => ['ar' => 'قاعة الغذائيات', 'en' => 'Food Industries Hall'],
                'description'    => null,
                'total_area_sqm' => 2000.00,
                'floor'          => 'Ground'
            ],
            [
                'name'           => ['ar' => 'قاعة الحرف اليدوية', 'en' => 'Handicrafts Hall'],
                'description'    => null,
                'total_area_sqm' => 1500.00,
                'floor'          => 'Ground'
            ],
            [
                'name'           => ['ar' => 'قاعة الصناعة والتقنية', 'en' => 'Industry & Technology Hall'],
                'description'    => null,
                'total_area_sqm' => 3500.00,
                'floor'          => 'Ground'
            ],
            [
                'name'           => ['ar' => 'قاعة الصحة والجمال', 'en' => 'Health & Beauty Hall'],
                'description'    => null,
                'total_area_sqm' => 1800.00,
                'floor'          => 'Ground'
            ],
        ];

        foreach ($halls as $hall) {
            Hall::firstOrCreate(
            // ابحث عن القاعة بناءً على الاسم باللغة الإنجليزية مثلاً لضمان الدقة
                ['name->en' => $hall['name']['en']],
                array_merge($hall, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $halls = Hall::all()->mapWithKeys(function ($item) {
            return [$item->getTranslation('name', 'ar') => $item];
        });


        $sectors = [
            ['name' => ['ar' => 'الصناعات الغذائية', 'en' => 'Food Industries'], 'hall_id' => 1],
            ['name' => ['ar' => 'الثقافة والفنون', 'en' => 'Culture & Arts'], 'hall_id' => 1],
            ['name' => ['ar' => 'الحرف اليدوية', 'en' => 'Handicrafts'], 'hall_id' => 2],
            ['name' => ['ar' => 'الصناعة والتقنية', 'en' => 'Industry & Technology'], 'hall_id' => 2],
            ['name' => ['ar' => 'الأنشطة الرياضية', 'en' => 'Sports Activities'], 'hall_id' => 3],
            ['name' => ['ar' => 'التعليم والبرامج', 'en' => 'Education & Programs'], 'hall_id' => 3],
            ['name' => ['ar' => 'الصحة والجمال', 'en' => 'Health & Beauty'], 'hall_id' => 4],
            ['name' => ['ar' => 'البناء والعقارات', 'en' => 'Construction & Real Estate'], 'hall_id' => 4],
            ['name' => ['ar' => 'الطاقة المتجددة', 'en' => 'Renewable Energy'], 'hall_id' => 5],
            ['name' => ['ar' => 'السيارات والمركبات', 'en' => 'Automobiles & Vehicles'], 'hall_id' => 5],
            ['name' => ['ar' => 'الموضة والأزياء', 'en' => 'Fashion & Clothing'], 'hall_id' => 6],
            ['name' => ['ar' => 'الإعلام والاتصالات', 'en' => 'Media & Communications'], 'hall_id' => 6],
        ];

        foreach ($sectors as $sectorData) {
            // 1. فحص وإنشاء القطاع بناءً على الاسم الإنجليزي لتجنب التكرار
            $sector = Sector::firstOrCreate(
                ['name->en' => $sectorData['name']['en']],
                [
                    // إدخال مصفوفة الترجمة كاملة لحقل الـ jsonb
                    'name' => $sectorData['name']
                ]
            );

            // 2. التحقق من وجود القاعة المستهدفة قبل عملية الربط لمنع الأخطاء الفادحة (Foreign Key Constraint)
            $hallExists = Hall::where('id', $sectorData['hall_id'])->exists();

            if ($hallExists) {
                // 3. ربط القطاع بالقاعة في جدول hall_sector
                // نستخدم syncWithoutDetaching بدلاً من attach لمنع تكرار نفس السطر إذا أعدتِ تشغيل الـ Seeder
                $sector->halls()->syncWithoutDetaching([$sectorData['hall_id']]);
            }
        }


        // 3. حلقة التكرار الكبيرة لإنشاء الشركات والطلبات (30 شركة)
        // جلب البيانات وتحويلها مباشرة إلى مصفوفة عادية تبدأ مؤشراتها من 0
        $sectors = Sector::all()->values()->all();
        $halls = Hall::all()->values()->all();
        $totalCompanies = 30;

        for ($i = 1; $i <= $totalCompanies; $i++) {
            // توزيع ديناميكي للقطاعات والقاعات بناءً على العداد
            $currentSector = $sectors[(($i - 1) % count($sectors))];
            $currentHall = $halls[(($i - 1) % count($halls))];

            // أ. إنشاء أو تحديث المستخدم (المعرّف الفرعي هو الـ email لضمان عدم التكرار)
            $user = User::firstOrNew(['email' => "vendor_co{$i}@exhibition.com"]);
            if (!$user->exists) {
                $user->id = (string) Str::uuid(); // تعيين الـ UUID فقط عند الإنشاء الجديد
            }
            $user->name = "مسؤول الحساب للشركة رقم " . $i;
            $user->phonenumber = "+96655" . str_pad($i, 7, '0', STR_PAD_LEFT);
            $user->password = Hash::make('123456789');
            $user->save();

            // ب. إنشاء أو تحديث الشركة (الربط الفريد مبني على الـ user_id)
            $company = Company::firstOrNew(['user_id' => $user->id]);
            $company->name = [
                'ar' => "شركة النخبة والريادة طراز (" . $i . ")",
                'en' => "Elite & Leadership Co. Type (" . $i . ")"
            ];
            $company->logo = "logos/company_avatar_{$i}.png";
            $company->responsible_person = "المهندس فهد العتيبي " . $i;
            $company->sector = $currentSector->name;
            $company->sector_id = $currentSector->id;
            $company->bio = [
                'ar' => "منشأة رائدة متخصصة في حلول وتطبيقات الـ " . $currentSector->name,
                'en' => "A leading firm specialized in solutions for " . $currentSector->name
            ];
            $company->nationality = ['ar' => 'سعودية', 'en' => 'Saudi'];
            $company->address = ['ar' => 'الرياض، البرج الرقمي، الدور 14', 'en' => 'Riyadh, Digital Tower, Fl 14'];
            $company->final_area = 36.0; // المساحة الإجمالية المستغلة من الطلبات المقبولة
            $company->booth_type = 'Equipped Booth';
            $company->is_active = true;
            $company->save();

            // [تطبيق الـ Polymorphism هنا]: بما أن الشركة تم قبولها، نربط مستنداتها الرسمية بـ الـ Company Model مباشرة
            $companyDoc = CompanyDocument::firstOrNew([
                'documentable_type' => Company::class,
                'documentable_id' => $company->id,
                'file_type' => 'commercial_register'
            ]);
            $companyDoc->file_path = "https://graduation-project1.s3.eu-north-1.amazonaws.com/company_documents/co_{$company->id}_cr.pdf";
            $companyDoc->save();
            $user->assignRole('company');

            $contractDoc = CompanyDocument::firstOrNew([
                'documentable_type' => Company::class,
                'documentable_id' => $company->id,
                'file_type' => 'contract'
            ]);
            $contractDoc->file_path = "uploads/documents/companies/co_{$company->id}_contract.pdf";
            $contractDoc->save();


            // ج. بناء الطلبات الثلاثة لكل شركة بناءً على محدداتك الصارمة:

            // ----------------------------------------------------
            // الطلب الأول (Req 1): مقبول + مدفوع + مسند ومربوط بـ Booth
            // ----------------------------------------------------
            $req1 = CompanyRequest::firstOrNew(['email' => "request_alpha_co{$i}@exhibition.com"]);
            $req1->foreign_local = 'local';
            $req1->company_name = $company->name;
            $req1->responsible_name = $company->responsible_person;
            $req1->job_title = 'Director General';
            $req1->phone = $user->phonenumber;
            $req1->nationality = $company->nationality;
            $req1->commercial_register = '1010' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $req1->address = $company->address;
            $req1->sector = $company->sector;
            $req1->company_description = [
                'ar' => "طلب حجز الجناح الرئيسي للمشاركة بمنتجاتنا السنوية",
                'en' => "Main booth request for displaying annual solutions."
            ];
            $req1->company_id = $company->id;
            $req1->requested_area = 18.0;
            $req1->setup_preference = 'Equipped Booth';
            $req1->terms_accepted_at = now();
            $req1->request_status = 'approved'; // مقبول
            $req1->payment_status = 'paid';     // مدفوع
            $req1->total_price = 4500.00;
            $req1->required_deposit = 1500.00;
            $req1->paid_amount = 4500.00;
            $req1->payment_due_date = now()->addDays(7);
            $req1->save();

            // إنشاء البوث وإسناده لهذا الطلب (المفتاح الفريد رقم البوث لضمان عدم التكرار)
            $booth = Booth::firstOrNew(['booth_number' => "B-H{$currentHall->id}-" . str_pad($i, 3, '0', STR_PAD_LEFT)]);
            $booth->booth_type = 'display';
            $booth->equipment_type = 'Equipped Booth';
            $booth->size_sqm = 18.0;
            $booth->available = false; // طالما مسند لشركة فهو غير متاح للعامة
            $booth->sector_id = $currentSector->id;
            $booth->company_id = $company->id;
            $booth->hall_id = $currentHall->id;
            $booth->company_request_id = $req1->id; // ربطه بالطلب الأول
            $booth->save();


            // ----------------------------------------------------
            // الطلب الثاني (Req 2): مقبول + مدفوع + غييير مسند لـ Booth
            // ----------------------------------------------------
            $req2 = CompanyRequest::firstOrNew(['email' => "request_beta_co{$i}@exhibition.com"]);
            $req2->foreign_local = 'local';
            $req2->company_name = $company->name;
            $req2->responsible_name = $company->responsible_person;
            $req2->job_title = 'Director General';
            $req2->phone = $user->phonenumber;
            $req2->nationality = $company->nationality;
            $req2->commercial_register = '1010' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $req2->address = $company->address;
            $req2->sector = $company->sector;
            $req2->company_description = [
                'ar' => "طلب مساحة أرضية إضافية بدون تجهيزات لعرض اللوحات الإعلانية فقط",
                'en' => "Row space requested for branding and standalone panels."
            ];
            $req2->company_id = $company->id;
            $req2->requested_area = 18.0;
            $req2->setup_preference = 'Row Space Only';
            $req2->terms_accepted_at = now();
            $req2->request_status = 'approved'; // مقبول
            $req2->payment_status = 'paid';     // مدفوع
            $req2->total_price = 3000.00;
            $req2->required_deposit = 1000.00;
            $req2->paid_amount = 3000.00;
            $req2->payment_due_date = now()->addDays(12);
            $req2->save();
            // هنا لا نقوم بإنشاء أي Booth أو إسناده ليبقى الطلب مقبولاً ومدفوعاً لكن دون حجز مكاني في الخريطة


            // ----------------------------------------------------
            // الطلب الثالث (Req 3): غير مقبول (معلق/مرفوض) أو غير مدفوع
            // ----------------------------------------------------
            $req3 = CompanyRequest::firstOrNew(['email' => "request_gamma_co{$i}@exhibition.com"]);
            $req3->foreign_local = 'foreign';
            $req3->company_name = $company->name;
            $req3->responsible_name = $company->responsible_person;
            $req3->job_title = 'Director General';
            $req3->phone = $user->phonenumber;
            $req3->nationality = ['ar' => 'إماراتية', 'en' => 'Emirati'];
            $req3->commercial_register = '2020' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $req3->address = ['ar' => 'دبي، منطقة دبي الحرة', 'en' => 'Dubai, Free Zone'];
            $req3->sector = $company->sector;
            $req3->company_description = [
                'ar' => "طلب كشك فرعي للمبيعات السريعة - قيد المراجعة والتدقيق المالي",
                'en' => "Kiosk request for fast sales - waiting review."
            ];
            $req3->company_id = null;
            $req3->requested_area = 12.0;
            $req3->setup_preference = 'Kiosk AB';
            $req3->terms_accepted_at = null;
            $req3->request_status = ($i % 2 == 0) ? 'pending' : 'rejected'; // توزيع بين معلق ومرفوض
            $req3->payment_status = 'unpaid'; // غير مدفوع
            $req3->total_price = 2000.00;
            $req3->required_deposit = 500.00;
            $req3->paid_amount = 0.00;
            $req3->payment_due_date = null;
            $req3->save();


            // د. إنشاء المنتجات والصور الترويجية المرتبطة بكل شركة لإثراء البيانات
            for ($p = 1; $p <= 2; $p++) {
                $product = Product::firstOrNew([
                    'company_id' => $company->id,
                    'price' => 200.00 + ($i * 10) + ($p * 5)
                ]);
                $product->name = [
                    'ar' => "نظام ذكي متطور إصدار " . ($i . "-" . $p),
                    'en' => "Smart System Evolution v" . ($i . "." . $p)
                ];
                $product->description = [
                    'ar' => "حلول تقنية تخصصية متوائمة مع المعايير الدولية لزيادة كفاءة الإنتاجية.",
                    'en' => "Professional tech solutions aligned with global standards."
                ];
                $product->save();

                // صور المنتجات
                $img = ProductImage::firstOrNew([
                    'product_id' => $product->id,
                    'image_path' => "assets/products/img_co_{$i}_prod_{$p}.png"
                ]);
                $img->is_primary = ($p == 1);
                $img->save();

                // هـ. العروض التسويقية (Promotions)
                if ($p == 1) {
                    $promo = Promotion::firstOrNew([
                        'company_id' => $company->id,
                        'discount_percentage' => 10 + ($i % 4) * 5
                    ]);
                    $promo->type = 'discount';
                    $promo->total_package_price = null;
                    $promo->start_date = now();
                    $promo->end_date = now()->addDays(20);
                    $promo->is_active = true;
                    $promo->save();

                    // ربط العرض بالمنتج
                    $promoProduct = PromotionProduct::firstOrNew([
                        'promotion_id' => $promo->id,
                        'product_id' => $product->id
                    ]);
                    $promoProduct->save();
                }
            }
        }

        // 4. تضخيم قاعدة البيانات: إضافة 20 بوث شاغر تماماً وغير مسند لأي شركة (للمحاكاة الشاملة)
        for ($b = 1; $b <= 20; $b++) {
            $randSector = $sectors[($b % count($sectors))];
            $randHall = $halls[($b % count($halls))];

            $vacantBooth = Booth::firstOrNew(['booth_number' => "B-VACANT-H{$randHall->id}-" . str_pad($b, 3, '0', STR_PAD_LEFT)]);
            $vacantBooth->booth_type = 'sales';
            $vacantBooth->equipment_type = 'Kiosk CD';
            $vacantBooth->size_sqm = 12.0;
            $vacantBooth->available = true; // متاح ومفتوح للحجوزات المستقبلية
            $vacantBooth->sector_id = $randSector->id;
            $vacantBooth->company_id = null;
            $vacantBooth->hall_id = $randHall->id;
            $vacantBooth->company_request_id = null;
            $vacantBooth->save();
        }

        $transportOptions = [
            [
                'name' => ['ar' => 'حافلات الترددية السريعة (شاتل)', 'en' => 'Exhibition Shuttle Bus'],
                'image' => 'assets/transport/shuttle.png',
                'google_maps_url' => 'https://maps.google.com/?q=24.7136,46.6753'
            ],
            [
                'name' => ['ar' => 'محطة مترو المعرض - البوابة 3', 'en' => 'Exhibition Metro Station - Gate 3'],
                'image' => 'assets/transport/metro.png',
                'google_maps_url' => 'https://maps.google.com/?q=24.7150,46.6780'
            ],
            [
                'name' => ['ar' => 'مواقف السيارات الفاخرة وكبار الشخصيات', 'en' => 'VIP Parking Area'],
                'image' => 'assets/transport/vip_parking.png',
                'google_maps_url' => 'https://maps.google.com/?q=24.7110,46.6740'
            ]
        ];

        foreach ($transportOptions as $key => $option) {
            $transport = Transportation::firstOrNew(['id' => $key + 1]);
            $transport->name = $option['name'];
            $transport->image = $option['image'];
            $transport->google_maps_url = $option['google_maps_url'];
            $transport->save();
        }

       /* // تعريف البوثات لكل قاعة
        $boothsConfig = [

            'قاعة الثقافة' => [
                ['booth_number' => 'C-A1', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 2],
                ['booth_number' => 'C-A2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 2],
                ['booth_number' => 'C-A3', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 2],
                ['booth_number' => 'C-A4', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 2],
                ['booth_number' => 'C-A5', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 2],
                ['booth_number' => 'C-B1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 2],
                ['booth_number' => 'C-B2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 2],
                ['booth_number' => 'C-B3', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 2],
                ['booth_number' => 'C-B4', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 2],
                ['booth_number' => 'C-B5', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 2],
            ],

            'القاعة الرياضية' => [
                ['booth_number' => 'S-A1', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 50.0,    'sector_id' => 5],
                ['booth_number' => 'S-A2', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 5],
                ['booth_number' => 'S-A3', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 5],
                ['booth_number' => 'S-A4', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 5],
                ['booth_number' => 'S-A5', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 5],
                ['booth_number' => 'S-B1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 5],
                ['booth_number' => 'S-B2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 5],
                ['booth_number' => 'S-B3', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 5],
                ['booth_number' => 'S-B4', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 5],
                ['booth_number' => 'S-B5', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 5],
            ],

            'قاعة الغذائيات' => [
                ['booth_number' => 'F-A1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 1],
                ['booth_number' => 'F-A2', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 1],
                ['booth_number' => 'F-A3', 'booth_type' => 'display',  'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 1],
                ['booth_number' => 'F-A4', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 1],
                ['booth_number' => 'F-A5', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 1],
                ['booth_number' => 'F-B1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 1],
                ['booth_number' => 'F-B2', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 1],
                ['booth_number' => 'F-B3', 'booth_type' => 'display',  'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 1],
                ['booth_number' => 'F-B4', 'booth_type' => 'sales',    'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 1],
                ['booth_number' => 'F-B5', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 1],
            ],

            'قاعة الحرف اليدوية' => [
                ['booth_number' => 'H-A1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 25.0,    'sector_id' => 3],
                ['booth_number' => 'H-A2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 3],
                ['booth_number' => 'H-A3', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 25.0,    'sector_id' => 3],
                ['booth_number' => 'H-A4', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 20.0,    'sector_id' => 3],
                ['booth_number' => 'H-A5', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 3],
                ['booth_number' => 'H-B1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 3],
                ['booth_number' => 'H-B2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 25.0,    'sector_id' => 3],
                ['booth_number' => 'H-B3', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 25.0,    'sector_id' => 3],
                ['booth_number' => 'H-B4', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 20.0,    'sector_id' => 3],
                ['booth_number' => 'H-B5', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 3],
            ],

            'قاعة الصناعة والتقنية' => [
                ['booth_number' => 'T-A1', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 4],
                ['booth_number' => 'T-A2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 4],
                ['booth_number' => 'T-A3', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 4],
                ['booth_number' => 'T-A4', 'booth_type' => 'display',  'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 48.0,    'sector_id' => 4],
                ['booth_number' => 'T-A5', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 36.0,    'sector_id' => 4],
                ['booth_number' => 'T-B1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 4],
                ['booth_number' => 'T-B2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 4],
                ['booth_number' => 'T-B3', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 36.0,    'sector_id' => 4],
                ['booth_number' => 'T-B4', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 36.0,    'sector_id' => 4],
                ['booth_number' => 'T-B5', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 4],
            ],

            'قاعة الصحة والجمال' => [
                ['booth_number' => 'B-A1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 7],
                ['booth_number' => 'B-A2', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 7],
                ['booth_number' => 'B-A3', 'booth_type' => 'display',  'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 25.0,    'sector_id' => 7],
                ['booth_number' => 'B-A4', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 48.0,    'sector_id' => 7],
                ['booth_number' => 'B-A5', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 7],
                ['booth_number' => 'B-B1', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 7],
                ['booth_number' => 'B-B2', 'booth_type' => 'display',  'equipment_type' => 'Equipped Booth',       'size_sqm' => 36.0,    'sector_id' => 7],
                ['booth_number' => 'B-B3', 'booth_type' => 'sales',    'equipment_type' => 'Not Equipped Booth',   'size_sqm' => 25.0,    'sector_id' => 7],
                ['booth_number' => 'B-B4', 'booth_type' => 'display',  'equipment_type' => 'Row Space Only',     'size_sqm' => 25.0,    'sector_id' => 7],
                ['booth_number' => 'B-B5', 'booth_type' => 'sales',    'equipment_type' => 'Equipped Booth',       'size_sqm' => 60.0,    'sector_id' => 7],
            ],
        ];

        foreach ($boothsConfig as $hallName => $booths) {
            $hall = $halls->get($hallName);
            if (! $hall) continue;

            foreach ($booths as $boothData) {
                Booth::firstOrCreate(
                    [
                        'booth_number' => $boothData['booth_number'],
                    ],
                    array_merge($boothData, [
                        'available'  => true,
                        'company_id' => null,
                        'company_request_id' => null,
                        'hall_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        $requests = [
            // ─── 1. سماكة للغذائيات ───────────────────────
            [
                'foreign_local'       => 'local',
                'company_name'        => ['ar' => 'سماكة للغذائيات', 'en' => 'Samakia Food'],
                'responsible_name'    => 'محمد علي سماكة',
                'job_title'           => 'المدير التنفيذي',
                'email'               => 'request@samakia.sy', // تصحيح الإيميل ليكون فريداً
                'phone'               => '+963 11 445 678',
                'nationality'         => ['ar' => 'سورية', 'en' => 'Syrian'],
                'commercial_register' => 'DM-2019-0044',
                'address'             => ['ar' => 'دمشق — شارع الثورة — بناء رقم 12', 'en' => 'Damascus - Thawra St - Bldg 12'],
                'sector'              => 'الصناعات الغذائية',
                'company_description' => [
                    'ar' => 'شركة رائدة في تصنيع وتوزيع المنتجات الغذائية والسمك المجفف والمعلب بأعلى معايير الجودة.',
                    'en' => 'A leading company in manufacturing and distributing food products and canned fish.'
                ],
                'requested_area'      => 48.0,
                'setup_preference'    => 'Equipped Booth',
                'terms_accepted_at'   => now()->subDays(30),
                'request_status'      => 'approved',
                'payment_status'      => 'paid',
                'total_price'         => 2400.00,
                'required_deposit'    => 1200.00,
                'paid_amount'         => 2400.00,
                'payment_due_date'    => now()->subDays(20)->toDateString(),
                'company_id'         => 1,
            ],

            // ─── 2. الحسن للسجاد ──────────────────────────
            [
                'foreign_local'       => 'local',
                'company_name'        => ['ar' => 'الحسن للسجاد', 'en' => 'Al-Hasan Carpets'],
                'responsible_name'    => 'عبدالله محمد الحسن',
                'job_title'           => 'مالك الشركة',
                'email'               => 'request@alhasan-carpet.sy',
                'phone'               => '+963 11 556 7890',
                'nationality'         => ['ar' => 'سورية', 'en' => 'Syrian'],
                'commercial_register' => 'DM-2015-00228',
                'address'             => ['ar' => 'دمشق — المزة — شارع الجلاء', 'en' => 'Damascus - Mazzeh - Al-Jalaa St'],
                'sector'              => 'الحرف اليدوية',
                'company_description' => [
                    'ar' => 'نحيك السجاد يدوياً بطريقة حسن بأدق التفاصيل وأجود أنواع الأصواف والحرير الطبيعي.',
                    'en' => 'We weave carpets manually with fine details and the best types of wool and natural silk.'
                ],
                'requested_area'      => 36.0,
                'setup_preference'    => 'Kiosk AB',
                'terms_accepted_at'   => now()->subDays(35),
                'request_status'      => 'approved',
                'payment_status'      => 'paid',
                'total_price'         => 1800.00,
                'required_deposit'    => 900.00,
                'paid_amount'         => 1800.00,
                'payment_due_date'    => now()->subDays(25)->toDateString(),
                'company_id'         => 2,
            ],

            // ─── 3. نشبه بعضنا (Foreign) ──────────────────
            [
                'foreign_local'       => 'foreign',
                'company_name'        => ['ar' => 'مؤسسة نشبه بعضنا', 'en' => 'Nashbeh Baadhna Foundation'],
                'responsible_name'    => 'فيصل الرشيد',
                'job_title'           => 'المدير الإقليمي',
                'email'               => 'request@nashbehbaadhna.sy',
                'phone'               => '+966 50 123 4567',
                'nationality'         => ['ar' => 'سعودية', 'en' => 'Saudi'],
                'commercial_register' => 'SA-2020-00991',
                'address'             => ['ar' => 'الرياض — حي العليا — طريق الملك فهد', 'en' => 'Riyadh - Olaya Dist - King Fahd Rd'],
                'sector'              => 'الثقافة والفنون',
                'company_description' => [
                    'ar' => 'مؤسسة سعودية تعزز الواقع العربي وتدعم المشاريع الثقافية والاجتماعية في المنطقة العربية.',
                    'en' => 'A Saudi institution that promotes Arab reality and supports cultural and social projects.'
                ],
                'requested_area'      => 60.0,
                'setup_preference'    => 'Equipped Booth',
                'terms_accepted_at'   => now()->subDays(28),
                'request_status'      => 'approved',
                'payment_status'      => 'unpaid',
                'total_price'         => 3500.00,
                'required_deposit'    => 1750.00,
                'paid_amount'         => 3500.00,
                'payment_due_date'    => now()->subDays(18)->toDateString(),
                'company_id'         => null,
            ],

            // ─── 4. حكاية المعارف ─────────────────────────
            [
                'foreign_local'       => 'local',
                'company_name'        => ['ar' => 'حكاية المعارف في أرض الطرابيش', 'en' => 'Hikayat Al-Maaref'],
                'responsible_name'    => 'ليلى الخوري',
                'job_title'           => 'مدير المشروع',
                'email'               => 'request@hikayat.sy',
                'phone'               => '+963 11 678 9012',
                'nationality'         => ['ar' => 'سورية', 'en' => 'Syrian'],
                'commercial_register' => 'DM-2021-00554',
                'address'             => ['ar' => 'دمشق — باب توما — شارع الأنصاري', 'en' => 'Damascus - Bab Touma - Al-Ansari St'],
                'sector'              => 'الثقافة والفنون',
                'company_description' => [
                    'ar' => 'مشروع ثقافي يروي تاريخ دمشق من خلال الفنون التقليدية والحرف الأصيلة.',
                    'en' => 'A cultural project telling the history of Damascus through traditional arts.'
                ],
                'requested_area'      => 36.0,
                'setup_preference'    => 'Not Equipped Booth',
                'terms_accepted_at'   => now()->subDays(22),
                'request_status'      => 'approved',
                'payment_status'      => 'unpaid',
                'total_price'         => 1500.00,
                'required_deposit'    => 750.00,
                'paid_amount'         => 1500.00,
                'payment_due_date'    => now()->subDays(12)->toDateString(),
                'company_id'         => null,
            ],

            // ─── 5. المنير للفخار ─────────────────────────
            [
                'foreign_local'       => 'local',
                'company_name'        => ['ar' => 'المنير للفخار والخزف', 'en' => 'Al-Moneer Pottery'],
                'responsible_name'    => 'كريم المنير',
                'job_title'           => 'صاحب الورشة',
                'email'               => 'request@almoneer.sy',
                'phone'               => '+963 11 890 1234',
                'nationality'         => ['ar' => 'سورية', 'en' => 'Syrian'],
                'commercial_register' => 'DM-2010-00115',
                'address'             => ['ar' => 'دمشق — القيمرية — زقاق الفخارين', 'en' => 'Damascus - Qaymariyah - Potters Alley'],
                'sector'              => 'الحرف اليدوية',
                'company_description' => [
                    'ar' => 'ورشة حرفية متخصصة في صناعة الفخار والخزف الدمشقي بأساليب تقليدية موروثة.',
                    'en' => 'A craft workshop specialized in Damascene pottery and ceramics using inherited methods.'
                ],
                'requested_area'      => 25.0,
                'setup_preference'    => 'Kiosk AB',
                'terms_accepted_at'   => now()->subDays(40),
                'request_status'      => 'approved',
                'payment_status'      => 'paid',
                'total_price'         => 1250.00,
                'required_deposit'    => 625.00,
                'paid_amount'         => 1250.00,
                'payment_due_date'    => now()->subDays(30)->toDateString(),
            ],

            // ─── 6. الطاقة الشمسية (Pending) ────────────────────
            [
                'foreign_local'       => 'local',
                'company_name'        => ['ar' => 'شركة الطاقة الشمسية السورية', 'en' => 'Syrian Solar Energy Co.'],
                'responsible_name'    => 'علاء الدين الجمال',
                'job_title'           => 'مدير التطوير',
                'email'               => 'request@solarenergy.sy',
                'phone'               => '+963 11 456 7890',
                'nationality'         => ['ar' => 'سورية', 'en' => 'Syrian'],
                'commercial_register' => 'DM-2022-00765',
                'address'             => ['ar' => 'دمشق — برزة — المنطقة الصناعية', 'en' => 'Damascus - Barzeh - Industrial Zone'],
                'sector'              => 'الطاقة المتجددة',
                'company_description' => [
                    'ar' => 'شركة متخصصة في تصميم وتركيب وصيانة منظومات الطاقة الشمسية للمنازل والمنشآت.',
                    'en' => 'A company specialized in designing and installing solar energy systems.'
                ],
                'requested_area'      => 48.0,
                'setup_preference'    => 'Row Space Only',
                'terms_accepted_at'   => now()->subDays(2),
                'request_status'      => 'pending',
                'payment_status'      => 'unpaid',
                'total_price'         => null,
                'required_deposit'    => null,
                'paid_amount'         => 0.00,
                'payment_due_date'    => null,
                'company_id'         => null,
            ],
        ];

        foreach ($requests as $request) {
            $com = CompanyRequest::firstOrCreate(
                ['email' => $request['email']],
                array_merge($request, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
            CompanyDocument::firstOrCreate([
                'documentable_type' => CompanyRequest::class, // سيعطي 'App\Models\Companyest'Requ
                'documentable_id'   => $com->id,
                'file_path'         => 'company_documents/register_3.pdf',
                'file_type'         => 'commercial_register'
            ]);
        }


        // ─── Gate Operator Users ──────────────────────────────────
        $gateOperators = [
            [
                'name'  => 'أمين البوابة الرئيسية',
                'email' => 'gate1@damascusfair.sy',
                'phone' => '111111111'
            ],
            [
                'name'  => 'أمين البوابة الجانبية',
                'email' => 'gate2@damascusfair.sy',
                'phone' => '222222222'
            ],
        ];

        foreach ($gateOperators as $operator) {
            $u = User::firstOrCreate(
                ['email' => $operator['email']],
                [
                    'name'            => $operator['name'],
                    'email'           => $operator['email'],
                    'password'        => Hash::make('Gate@1234!'),
                    'phonenumber'     => $operator['phone'],
                    'failed_attempts' => 0,
                    'locked_until'    => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
            $u->assignRole('gate_operator');
        }

        // 1. إنشاء المستخدمين وتعيين الأدوار (تأكدي من تفعيل السطر الأخير)
        $companyUsers = [
            ['name' => 'مستخدم سماكة للغذائيات', 'email' => 'user@samakia.sy', 'phone' => '333333333'],
            ['name' => 'مستخدم الحسن للسجاد', 'email' => 'user@alhasan-carpet.sy', 'phone' => '444444444'],
            ['name' => 'مستخدم المنير للفخار', 'email' => 'user@almoneer.sy', 'phone' => '888888888'],
            ['name' => 'مستخدم شركة التقنيات المتقدمة', 'email' => 'user@advanced-tech.sy', 'phone' => '999999999'],
            ['name' => 'مستخدم مؤسسة الأزياء السورية', 'email' => 'user@syrianfashion.sy', 'phone' => '222111333'],
            ['name' => 'مستخدم مركز الصحة الشاملة', 'email' => 'user@healthcenter.sy', 'phone' => '333222111'],
        ];

        foreach ($companyUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $userData['name'],
                    'password' => Hash::make('Company@1234!'),
                    'phonenumber' => $userData['phone'],
                ]
            );

            // تفعيل السطر لحل مشكلة الـ Login والـ Resource
            $user->assignRole('company');
        }

// 2. بيانات الشركات والطلبات المعتمدة
        $companiesData = [
            [
                'user_email' => 'user@samakia.sy',
                'request_email' => 'request@samakia.sy',
                'name' => ['ar' => 'سماكة للغذائيات', 'en' => 'Samakia Food'],
                'logo' => 'logos/samakia.png',
                'responsible_person' => 'محمد علي سماكة',
                'sector' => 'الصناعات الغذائية',
                'sector_id' => 1,
                'bio' => [
                    'ar' => 'شركة رائدة في تصنيع وتوزيع المنتجات الغذائية والسمك المجفف والمعلب بأعلى معايير الجودة.',
                    'en' => 'A leading company in manufacturing and distributing food products and canned fish.'
                ],
                'address' => ['ar' => 'دمشق — شارع الثورة', 'en' => 'Damascus - Thawra St'],
                'final_area' => 48.0,
                'booth_type' => 'Equipped Booth',
            ],
            [
                'user_email' => 'user@alhasan-carpet.sy',
                'request_email' => 'request@alhasan-carpet.sy',
                // تم توحيد الاسم هنا ليتطابق مع مصفوفة المنتجات تحت ومع السيدر الآخر
                'name' => ['ar' => 'الحسن للسجاد بالطريقة اليدوية', 'en' => 'Al-Hasan Hand-woven Carpets'],
                'logo' => 'logos/alhasan.png',
                'responsible_person' => 'عبدالله محمد الحسن',
                'sector' => 'الحرف اليدوية',
                'sector_id' => 2,
                'bio' => [
                    'ar' => 'نحيك السجاد يدوياً بأدق التفاصيل وأجود أنواع الأصواف والحرير الطبيعي.',
                    'en' => 'We weave carpets manually with fine details and the best types of wool and natural silk.'
                ],
                'address' => ['ar' => 'دمشق — المزة', 'en' => 'Damascus - Mazzeh'],
                'final_area' => 36.0,
                'booth_type' => 'Kiosk AB',
            ],
            [
                'user_email' => 'user@almoneer.sy',
                'request_email' => 'request@almoneer.sy',
                'name' => ['ar' => 'المنير للفخار والخزف', 'en' => 'Al-Moneer Pottery'],
                'logo' => 'logos/almoneer.png',
                'responsible_person' => 'كريم المنير',
                'sector' => 'الحرف اليدوية',
                'sector_id' => 2,
                'bio' => [
                    'ar' => 'ورشة حرفية متخصصة في صناعة الفخار والخزف الدمشقي بأساليب تقليدية موروثة.',
                    'en' => 'A craft workshop specialized in Damascene pottery using inherited traditional methods.'
                ],
                'address' => ['ar' => 'دمشق — القيمرية', 'en' => 'Damascus - Qaymariyah'],
                'final_area' => 25.0,
                'booth_type' => 'Kiosk AB',
            ],
            [
                'user_email' => 'user@advanced-tech.sy',
                'request_email' => 'request@advanced-tech.sy',
                'name' => ['ar' => 'شركة التقنيات المتقدمة', 'en' => 'Advanced Tech Co.'],
                'logo' => 'logos/advancedtech.png',
                'responsible_person' => 'رامي السيد',
                'sector' => 'الصناعة والتقنية',
                'sector_id' => 3,
                'bio' => [
                    'ar' => 'شركة سورية رائدة في تطوير حلول تقنية ذكية للمؤسسات والأفراد.',
                    'en' => 'A leading Syrian company in developing smart technical solutions.'
                ],
                'address' => ['ar' => 'دمشق — كفرسوسة', 'en' => 'Damascus - Kafr Sousa'],
                'final_area' => 60.0,
                'booth_type' => 'Equipped Booth',
            ],
            [
                'user_email' => 'user@syrianfashion.sy',
                'request_email' => 'request@syrianfashion.sy',
                'name' => ['ar' => 'مؤسسة الأزياء السورية', 'en' => 'Syrian Fashion Foundation'],
                'logo' => 'logos/syrianfashion.png',
                'responsible_person' => 'هالة الزعبي',
                'sector' => 'الموضة والأزياء',
                'sector_id' => 4,
                'bio' => [
                    'ar' => 'مؤسسة للتصميم والإنتاج في صناعة الأزياء السورية الأصيلة المزوجة بالطراز الحديث.',
                    'en' => 'A foundation for design and production in the authentic Syrian fashion industry.'
                ],
                'address' => ['ar' => 'دمشق — الشعلان', 'en' => 'Damascus - Shaalan'],
                'final_area' => 36.0,
                'booth_type' => 'Equipped Booth',
            ],
            [
                'user_email' => 'user@healthcenter.sy',
                'request_email' => 'request@healthcenter.sy',
                'name' => ['ar' => 'مركز الصحة الشاملة', 'en' => 'Comprehensive Health Center'],
                'logo' => 'logos/healthcenter.png',
                'responsible_person' => 'د. نور السالم',
                'sector' => 'الصحة والجمال',
                'sector_id' => 5,
                'bio' => [
                    'ar' => 'مركز صحي شامل يقدم خدمات الرعاية الصحية الوقائية والعلاجية والجمالية.',
                    'en' => 'A comprehensive health center providing preventive, therapeutic, and aesthetic care.'
                ],
                'address' => ['ar' => 'دمشق — المهاجرين', 'en' => 'Damascus - Muhajirin'],
                'final_area' => 36.0,
                'booth_type' => 'Equipped Booth',
            ],
        ];

        foreach ($companiesData as $data) {
            $user = User::where('email', $data['user_email'])->first();

            $request = CompanyRequest::updateOrCreate(
                ['email' => $data['request_email']],
                [
                    'foreign_local'       => 'local',
                    'company_name'        => $data['name'],
                    'responsible_name'    => $data['responsible_person'],
                    'job_title'           => 'المدير التنفيذي',
                    // حل مشكلة عدم وجود الـ phone في الـ data
                    'phone'               => $user ? $user->phonenumber : '0000000000',
                    'nationality'         => ['ar' => 'سورية', 'en' => 'Syrian'],
                    'commercial_register' => 'CR-' . rand(1000, 9999),
                    'address'             => $data['address'],
                    'sector'              => $data['sector'],
                    'company_description' => $data['bio'],
                    'requested_area'      => $data['final_area'],
                    'setup_preference'    => $data['booth_type'],
                    'terms_accepted_at'   => now(),
                    'request_status'      => 'approved',
                    'payment_status'      => 'paid',
                    'total_price'         => $data['final_area'] * 50,
                    'paid_amount'         => $data['final_area'] * 50,
                    'required_deposit'    => ($data['final_area'] * 50) / 2,
                ]
            );

            if ($user) {
                Company::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name'               => $data['name'],
                        'logo'               => $data['logo'],
                        'responsible_person' => $data['responsible_person'],
                        'sector'             => $data['sector'],
                        'sector_id'          => $data['sector_id'],
                        'bio'                => $data['bio'],
                        'address'            => $data['address'],
                        'final_area'         => $data['final_area'],
                        'booth_type'         => $data['booth_type'],
                        'nationality'        => ['ar' => 'سورية', 'en' => 'Syrian'],
                        'is_active'          => true,
                    ]
                );
            }
        }

// 3. بيانات المنتجات (الأسماء متطابقة تماماً الآن)
        $productsData = [
            'سماكة للغذائيات' => [
                [
                    'name'        => ['ar' => 'سمك مجفف ممتاز', 'en' => 'Premium Dried Fish'],
                    'description' => [
                        'ar' => 'سمك مجفف طبيعي 100% بدون إضافات صناعية، يُحضَّر بطريقة تقليدية ومعبأ في أكياس 500 غرام.',
                        'en' => '100% natural dried fish without artificial additives, prepared traditionally and packed in 500g bags.'
                    ],
                    'price'       => 4500.00,
                    'images'      => [
                        ['path' => 'products/samakia/dried-fish.jpg', 'is_primary' => true],
                        ['path' => 'products/samakia/dried-fish-2.jpg', 'is_primary' => false],
                    ],
                ],
                // ... بقية منتجات سماكة
            ],

            'الحسن للسجاد بالطريقة اليدوية' => [
                [
                    'name'        => ['ar' => 'سجادة حمراء تراثية', 'en' => 'Traditional Red Carpet'],
                    'description' => [
                        'ar' => 'سجادة يدوية حمراء بزخارف دمشقية أصيلة، مصنوعة من أجود أنواع الصوف الطبيعي، مقاس 200×200 سم.',
                        'en' => 'Handmade red carpet with authentic Damascene patterns, made of the finest natural wool, size 200x200 cm.'
                    ],
                    'price'       => 200.00,
                    'images'      => [
                        ['path' => 'products/alhasan/red-carpet.jpg', 'is_primary' => true],
                    ],
                ],
                // ... بقية منتجات السجاد
            ],

            // ... بقية المصفوفات كما هي دون تعديل
        ];

        foreach ($productsData as $companyName => $products) {
            // جلب الشركة بالاعتماد على حقل الـ JSON المترجم (pgsql سليم 100%)
            $company = Company::where('name->ar', $companyName)->first();
            if (!$company) continue;

            foreach ($products as $pData) {
                $product = Product::firstOrCreate(
                    ['company_id' => $company->id, 'name->ar' => $pData['name']['ar']],
                    [
                        'name' => $pData['name'],
                        'description' => $pData['description'],
                        'price' => $pData['price'],
                    ]
                );

                if ($product->wasRecentlyCreated) {
                    foreach ($pData['images'] as $img) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $img['path'],
                            'is_primary' => $img['is_primary'],
                        ]);
                    }
                }
            }
        }*/
    }
}
