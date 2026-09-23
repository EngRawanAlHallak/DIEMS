<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sector;
use App\Models\Hall;
use App\Models\Company;
use App\Models\CompanyRequest;
use App\Models\EventSlot;
use App\Models\EventRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;


class ExhibitionDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Exhibit@2026Demo';

    /** @var array<string,int> sector "en" name => sector id */
    private array $sectorIds = [];

    /** @var array<int,int> list of hall ids */
    private array $hallIds = [];

    public function run(): void
    {
        mt_srand(20260822); // deterministic-ish "randomness" so reruns are stable if table is fresh

        DB::transaction(function () {
            $this->seedSectors();
            $this->seedHalls();
            $slotIds = $this->seedEventSlots();
            $this->seedCompanies();
            $this->seedEvents($slotIds);
        });

        $this->command?->info('Exhibition demo data seeded: sectors, halls, event slots, 50 company requests, 20 event requests.');
        $this->command?->warn('Every seeded user password is: ' . self::DEMO_PASSWORD);
    }

    /* ---------------------------------------------------------------- */
    /*  Sectors                                                          */
    /* ---------------------------------------------------------------- */

    private function seedSectors(): void
    {
        // The 6 sectors this seeder needs (5 you already have + Construction).
        $required = [
            'Food Industries'       => 'الصناعة الغذائية',
            'Industry & Technology' => 'الصناعة والتقنية',
            'Renewable Energy'      => 'الطاقة المتجددة',
            'Technology'            => 'تكنولوجيا',
            'Trade and Services'    => 'التجارة والخدمات',
            'Construction'          => 'البناء',
        ];

        // Read RAW rows straight from the DB (bypasses any Eloquent cast
        // mismatch that can silently break json_decode on $sector->name).
        $existing = DB::table('sectors')->select('id', 'name')->get();

        foreach ($existing as $row) {
            $raw = $row->name;

            // Handle every shape Postgres/MySQL jsonb/json driver might hand back:
            // a plain JSON string, a double-encoded string, or (via Eloquent) an array/object.
            $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
            if (is_string($decoded)) {
                // was double-encoded, decode once more
                $decoded = json_decode($decoded, true);
            }

            if (is_array($decoded) && !empty($decoded['en'])) {
                $this->sectorIds[$decoded['en']] = $row->id;
            }
        }

        // Self-heal: create whichever of the 6 required sectors don't already exist,
        // instead of assuming only "Construction" could be missing.
        foreach ($required as $en => $ar) {
            if (isset($this->sectorIds[$en])) {
                continue;
            }
            $newId = DB::table('sectors')->insertGetId([
                'name'       => json_encode(['ar' => $ar, 'en' => $en], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->sectorIds[$en] = $newId;
        }

        // Final safety net: if something is STILL missing (e.g. a required sector
        // key doesn't match anything above), fail loudly with a clear message
        // instead of a confusing "undefined array key" deep inside seedCompanies().
        foreach (array_keys($required) as $en) {
            if (!isset($this->sectorIds[$en])) {
                throw new \RuntimeException("ExhibitionDemoSeeder: could not resolve or create sector '{$en}'.");
            }
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Halls (matches the hall map image)                              */
    /* ---------------------------------------------------------------- */

    private function seedHalls(): void
    {
        $large  = ['H1', 'H2', 'H3', 'H10', 'H11', 'H12', 'H25', 'H26'];
        $medium = ['H4', 'H5', 'H6', 'H7', 'H8', 'H9', 'H13', 'H14', 'H15', 'H16', 'H17', 'H18',
            'H20', 'H21', 'H22', 'H23', 'H27', 'H28', 'H29', 'H30', 'H34', 'H35', 'H36'];
        $small  = ['H1.1', 'H10.1', 'H31', 'H32', 'H33', 'H37', 'H38', 'H39', 'H40', 'H41', 'H42', 'H43', 'H45', 'H46'];
        $special = ['VIP' => 300.0, 'PR' => 150.0];

        $codes = array_merge($large, $medium, $small);

        foreach ($codes as $code) {
            $area = in_array($code, $large, true) ? mt_rand(900, 1600)
                : (in_array($code, $medium, true) ? mt_rand(350, 700) : mt_rand(60, 180));

            $hall = Hall::create([
                'name'            => ['ar' => "قاعة {$code}", 'en' => "Hall {$code}"],
                'description'     => null,
                'floor'           => 'Ground',
                'total_area_sqm'  => $area,
            ]);
            $this->hallIds[] = $hall->id;
        }

        foreach ($special as $code => $area) {
            $label = $code === 'VIP' ? ['ar' => 'الجناح الملكي VIP', 'en' => 'VIP Pavilion'] : ['ar' => 'قاعة العلاقات العامة', 'en' => 'PR Hall'];
            $hall = Hall::create([
                'name'            => $label,
                'description'     => null,
                'floor'           => 'Ground',
                'total_area_sqm'  => $area,
            ]);
            $this->hallIds[] = $hall->id;
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Event slots: 26/8 -> 4/9, 5pm-11pm, 3 x 2h slots/day             */
    /* ---------------------------------------------------------------- */

    private function seedEventSlots(): array
    {
        $start = Carbon::create(2026, 8, 26);
        $end   = Carbon::create(2026, 9, 4);

        $windows = [
            ['17:00', '19:00'],
            ['19:00', '21:00'],
            ['21:00', '23:00'],
        ];

        $slotIds = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            foreach ($windows as [$startTime, $endTime]) {
                $slot = EventSlot::create([
                    'slot_date'  => $date->toDateString(),
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                    'available'  => true,
                ]);
                $slotIds[] = $slot->id;
            }
        }

        return $slotIds; // 10 days x 3 = 30 slots
    }

    /* ---------------------------------------------------------------- */
    /*  Companies: 50 company_requests, users for all non-rejected,      */
    /*  companies for approved ones                                     */
    /* ---------------------------------------------------------------- */

    private function seedCompanies(): void
    {
        $catalog = $this->companyCatalog(); // 50 entries, see below
        $statuses = $this->buildStatusPlan(50, [
            'approved'        => 30,
            'pending'         => 10,
            'rejected'        => 6,
            'action_required' => 3,
            'expired'         => 1,
        ]);

        $setupPreferences = ['Equipped Booth', 'Not Equipped Booth', 'Row Space Only', 'Kiosk AB', 'Kiosk CD'];
        $unitPricesBySector = [
            'Food Industries'        => 120,
            'Industry & Technology'  => 140,
            'Renewable Energy'       => 150,
            'Technology'             => 160,
            'Trade and Services'     => 110,
            'Construction'           => 130,
        ];

        foreach ($catalog as $i => $entry) {
            $status = $statuses[$i];
            $slug   = Str::slug($entry['en']);
            $email  = $slug . '@' . $this->demoDomain($i);
            $phone  = $this->syrianPhone();
            $foreignLocal = $entry['country'] === 'Syria' ? 'local' : 'foreign';
            $setup  = $setupPreferences[$i % count($setupPreferences)];
            $area   = mt_rand(20, 150);
            $unitPrice = $unitPricesBySector[$entry['sector']];
            $totalPrice = $area * $unitPrice;
            $deposit = round($totalPrice * 0.25, 2);

            $user = null;
            if ($status !== 'rejected') {
                $user = User::create([
                    'id'             => (string) Str::uuid(),
                    // Kept identical to the company name, as requested, for consistency across tables.
                    'name'           => $entry['ar'],
                    'phonenumber'    => $phone,
                    'email'          => $email,
                    'email_verified_at' => now(),
                    'password'       => Hash::make(self::DEMO_PASSWORD),
                ]);
                $user->assignRole('company');
            }

            $paymentStatus = 'unpaid';
            $paidAmount = 0;
            if ($status === 'approved') {
                $paymentStatus = mt_rand(0, 4) === 0 ? 'partial_paid' : 'paid';
                $paidAmount = $paymentStatus === 'paid' ? $totalPrice : $deposit;
            }

            $companyRequest = CompanyRequest::create([
                'foreign_local'        => $foreignLocal,
                'company_name'         => ['ar' => $entry['ar'], 'en' => $entry['en']],
                'responsible_name'     => $entry['responsible'],
                'job_title'            => $entry['job_title'],
                'email'                => $email,
                'phone'                => $phone,
                'nationality'          => ['ar' => $entry['nationality_ar'], 'en' => $entry['country']],
                'commercial_register'  => $this->commercialRegister($i),
                'address'              => ['ar' => $entry['address_ar'], 'en' => $entry['address_en']],
                'sector'               => $entry['sector'],
                'company_description'  => ['ar' => $entry['desc_ar'], 'en' => $entry['desc_en']],
                'requested_area'       => $area,
                'setup_preference'     => $setup,
                'terms_accepted_at'    => now(),
                'request_status'       => $status,
                'payment_status'       => $paymentStatus,
                'total_price'          => $totalPrice,
                'required_deposit'     => $deposit,
                'paid_amount'          => $paidAmount,
                'payment_due_date'     => $status === 'approved' ? now()->addDays(2) : now()->addDays(5),
                'admin_notes'          => $this->adminNote($status),
            ]);

            if ($status === 'approved' && $user) {
                $company = Company::create([
                    'user_id'            => $user->id,
                    'name'               => ['ar' => $entry['ar'], 'en' => $entry['en']],
                    'logo'               => "https://picsum.photos/seed/{$slug}-logo/400/400",
                    'responsible_person' => $entry['responsible'],
                    'sector'             => $entry['sector'],
                    'sector_id'          => $this->sectorIds[$entry['sector']],
                    'bio'                => ['ar' => $entry['desc_ar'], 'en' => $entry['desc_en']],
                    'nationality'        => ['ar' => $entry['nationality_ar'], 'en' => $entry['country']],
                    'address'            => ['ar' => $entry['address_ar'], 'en' => $entry['address_en']],
                    'final_area'         => $area,
                    'booth_type'         => $setup,
                    'is_active'          => true,
                ]);


                $companyRequest->company_id = $company->id;
                $companyRequest->save();
            }
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Events: 20 event_requests, approved ones consume a real slot     */
    /* ---------------------------------------------------------------- */

    private function seedEvents(array $slotIds): void
    {
        $events = $this->eventCatalog(); // 20 entries
        $statuses = $this->buildStatusPlan(20, [
            'approved'  => 12,
            'pending'   => 5,
            'rejected'  => 2,
            'cancelled' => 1,
        ]);

        shuffle($slotIds);
        $availableSlotPointer = 0;

        foreach ($events as $i => $entry) {
            $status = $statuses[$i];
            $slug   = Str::slug($entry['en_title']);
            $sectorId = $this->sectorIds[$entry['sector']];
            $hallId   = $this->hallIds[array_rand($this->hallIds)];

            $slotId = null;
            if ($status === 'approved') {
                $slotId = $slotIds[$availableSlotPointer++] ?? $slotIds[array_rand($slotIds)];
            } else {
                // pending/rejected/cancelled requests still reference a slot they *asked* for,
                // it just doesn't get locked as unavailable unless approved.
                $slotId = $slotIds[array_rand($slotIds)];
            }

            $eventRequest = EventRequest::create([
                'slot_id'              => $slotId,
                'sector_id'            => $sectorId,
                'hall_id'              => $hallId,
                'organizer_name'       => $entry['organizer'],
                'organizer_email'      => Str::slug($entry['organizer']) . '@' . $this->demoDomain($i + 50),
                'organizer_phone'      => $this->syrianPhone(),
                'event_title'          => ['ar' => $entry['ar_title'], 'en' => $entry['en_title']],
                'event_description'    => ['ar' => $entry['ar_desc'], 'en' => $entry['en_desc']],
                'Expected_attendance'  => $entry['attendance'],
                'equipment_needed'     => $entry['equipment'],
                'image'                => "https://picsum.photos/seed/{$slug}-banner/1200/600",
                'is_special'           => $entry['is_special'] ?? false,
                'request_status'       => $status,
                'payment_status'       => $status === 'approved' ? 'paid' : 'unpaid',
                'total_price'          => ($entry['is_special'] ?? false) ? 0 : mt_rand(300, 1500),
                'payment_due_date'     => $status === 'approved' ? now()->addDays(3) : now()->addDays(7),
            ]);

            if ($status === 'approved') {
                EventSlot::whereKey($eventRequest->slot_id)->update(['available' => false]);
            }
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Helpers                                                          */
    /* ---------------------------------------------------------------- */

    /** Build a shuffled status array of the requested size honoring exact counts. */
    private function buildStatusPlan(int $total, array $counts): array
    {
        $plan = [];
        foreach ($counts as $status => $count) {
            $plan = array_merge($plan, array_fill(0, $count, $status));
        }
        // pad/trim just in case counts don't add up exactly to $total
        $plan = array_slice(array_pad($plan, $total, 'pending'), 0, $total);
        shuffle($plan);
        return $plan;
    }

    private function syrianPhone(): string
    {
        $prefixes = ['93', '94', '95', '96', '98', '99'];
        $prefix = $prefixes[array_rand($prefixes)];
        $rest = str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        return '+963' . $prefix . $rest;
    }

    private function commercialRegister(int $i): string
    {
        return 'CR-' . mt_rand(2008, 2024) . '-' . str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT);
    }

    private function demoDomain(int $i): string
    {
        $domains = ['example.com', 'example.net', 'example.org']; // RFC 2606 reserved — safe, non-deliverable
        return $domains[$i % count($domains)];
    }

    private function adminNote(string $status): ?string
    {
        return match ($status) {
            'rejected'        => 'المستندات غير مكتملة / لا تتوافق مع شروط القطاع المطلوب.',
            'action_required' => 'مطلوب إرسال السجل التجاري الأصلي قبل المتابعة.',
            'expired'         => 'انتهت مهلة تأكيد الحجز دون استكمال الدفع.',
            default           => null,
        };
    }

    /**
     * 50 curated, plausible-but-fictional companies across the 6 sectors.
     * Names avoid real registered trademarks; countries reflect the mix
     * seen at real editions of the fair (mostly Syrian, plus a realistic
     * spread of Arab/regional participants).
     */
    private function companyCatalog(): array
    {
        $rows = [];

        // --- Food Industries (9) ---
        $food = [
            ['شركة الشام للصناعات الغذائية', 'Al-Sham Food Industries Co.', 'Syria', 'Damascus'],
            ['مجموعة الفرات لتعليب الأغذية', 'Euphrates Food Packing Group', 'Syria', 'Deir ez-Zor'],
            ['شركة دمشق للألبان والأجبان', 'Damascus Dairy & Cheese Co.', 'Syria', 'Damascus'],
            ['مؤسسة حلب للمواد الغذائية', 'Aleppo Foodstuff Est.', 'Syria', 'Aleppo'],
            ['شركة الواحة لتصنيع الحلويات', 'Al-Waha Confectionery Manufacturing Co.', 'Jordan', 'Amman'],
            ['مجموعة النخبة للزيوت النباتية', 'Al-Nokhba Vegetable Oils Group', 'Syria', 'Homs'],
            ['شركة الساحل لتعبئة المياه والعصائر', 'Coastal Water & Juice Bottling Co.', 'Syria', 'Lattakia'],
            ['مؤسسة بردى لصناعة المعلبات', 'Barada Canning Est.', 'Syria', 'Damascus'],
            ['شركة الأمل للمخابز والمعجنات', 'Al-Amal Bakery & Pastry Co.', 'Egypt', 'Cairo'],
        ];
        foreach ($food as $c) {
            $rows[] = $this->buildEntry($c, 'Food Industries', 'صناعات غذائية متكاملة تشمل التصنيع والتعبئة والتغليف وفق معايير الجودة والسلامة الغذائية.', 'An integrated food manufacturing, packing, and packaging operation built to modern food-safety and quality standards.');
        }

        // --- Industry & Technology (8) ---
        $industry = [
            ['شركة قاسيون للصناعات الهندسية', 'Qasioun Engineering Industries Co.', 'Syria', 'Damascus'],
            ['مجموعة الاتحاد للآلات والمعدات', 'Al-Ittihad Machinery & Equipment Group', 'Syria', 'Aleppo'],
            ['شركة تدمر للصناعات المعدنية', 'Tadmor Metal Industries Co.', 'Syria', 'Homs'],
            ['مؤسسة اليرموك للتصنيع الدقيق', 'Yarmouk Precision Manufacturing Est.', 'Syria', 'Damascus'],
            ['شركة الفارابي للأدوات الصناعية', 'Al-Farabi Industrial Tools Co.', 'Turkey', 'Gaziantep'],
            ['مجموعة الرافدين للصناعات البلاستيكية', 'Al-Rafidain Plastics Industries Group', 'Syria', 'Aleppo'],
            ['شركة النجمة للصناعات الكهربائية', 'Al-Najma Electrical Industries Co.', 'Syria', 'Damascus'],
            ['مؤسسة الجودة للتصنيع والتعبئة', 'Al-Jawda Manufacturing & Packaging Est.', 'Saudi Arabia', 'Riyadh'],
        ];
        foreach ($industry as $c) {
            $rows[] = $this->buildEntry($c, 'Industry & Technology', 'خطوط إنتاج صناعية متطورة للآلات والمعدات والصناعات المعدنية والبلاستيكية.', 'Advanced production lines covering machinery, metal, and plastics manufacturing.');
        }

        // --- Renewable Energy (8) ---
        $energy = [
            ['شركة الشمس للطاقة الشمسية', 'Al-Shams Solar Energy Co.', 'Syria', 'Damascus'],
            ['مجموعة ريف للطاقة المتجددة', 'Reef Renewable Energy Group', 'Syria', 'Homs'],
            ['شركة النور لأنظمة الطاقة النظيفة', 'Al-Nour Clean Energy Systems Co.', 'UAE', 'Dubai'],
            ['مؤسسة الرياح لحلول الطاقة البديلة', 'Al-Riyah Alternative Energy Solutions Est.', 'Syria', 'Tartus'],
            ['شركة المتوسط للطاقة الشمسية والتخزين', 'Mediterranean Solar & Storage Co.', 'Lebanon', 'Beirut'],
            ['مجموعة الاستدامة للطاقة الخضراء', 'Al-Istidama Green Energy Group', 'Syria', 'Damascus'],
            ['شركة الأفق للطاقة الكهروضوئية', 'Al-Ofok Photovoltaic Energy Co.', 'Syria', 'Aleppo'],
            ['مؤسسة الينابيع للطاقة المائية الصغيرة', 'Al-Yanabee Micro-Hydro Energy Est.', 'Syria', 'Lattakia'],
        ];
        foreach ($energy as $c) {
            $rows[] = $this->buildEntry($c, 'Renewable Energy', 'حلول طاقة شمسية ومتجددة للمنازل والمنشآت الصناعية والتجارية.', 'Solar and renewable energy solutions for residential, industrial, and commercial sites.');
        }

        // --- Technology (8) ---
        $tech = [
            ['شركة السحاب لتكنولوجيا المعلومات', 'Al-Sahab IT Solutions Co.', 'Syria', 'Damascus'],
            ['مجموعة الشبكة للحلول الرقمية', 'Al-Shabaka Digital Solutions Group', 'Syria', 'Damascus'],
            ['شركة كود سوريا للبرمجيات والتطبيقات', 'CodeSyria Software & Apps Co.', 'Syria', 'Aleppo'],
            ['مؤسسة البيانات الذكية للأنظمة', 'Smart Data Systems Est.', 'Jordan', 'Amman'],
            ['شركة الاتصالات الحديثة للتقنية', 'Modern Telecom Technology Co.', 'Syria', 'Homs'],
            ['مجموعة نبض للذكاء الاصطناعي', 'Nabd AI Solutions Group', 'Syria', 'Damascus'],
            ['شركة الشبكة الآمنة للأمن السيبراني', 'SecureNet Cybersecurity Co.', 'Turkey', 'Istanbul'],
            ['مؤسسة المستقبل الرقمي للحوسبة السحابية', 'Digital Future Cloud Computing Est.', 'Egypt', 'Cairo'],
        ];
        foreach ($tech as $c) {
            $rows[] = $this->buildEntry($c, 'Technology', 'حلول برمجية وتقنية للشركات تشمل التطبيقات، الحوسبة السحابية والأمن السيبراني.', 'Software and IT solutions for businesses, covering apps, cloud computing, and cybersecurity.');
        }

        // --- Trade and Services (9) ---
        $trade = [
            ['شركة الشام العالمية للاستيراد والتصدير', 'Al-Sham International Import & Export Co.', 'Syria', 'Damascus'],
            ['مجموعة الخليج للتجارة العامة', 'Gulf General Trading Group', 'Saudi Arabia', 'Jeddah'],
            ['شركة اللوجستيات المتحدة للخدمات', 'United Logistics Services Co.', 'Syria', 'Lattakia'],
            ['مؤسسة الأندلس للتوزيع والتجارة', 'Al-Andalus Distribution & Trade Est.', 'Syria', 'Damascus'],
            ['شركة الطريق الحريري للنقل والشحن', 'Silk Road Transport & Freight Co.', 'Turkey', 'Istanbul'],
            ['مجموعة الوسيط التجاري للخدمات', 'Al-Waseet Commercial Services Group', 'Syria', 'Homs'],
            ['شركة بوابة الشرق للتجارة الدولية', 'Gateway of the East International Trade Co.', 'Egypt', 'Alexandria'],
            ['مؤسسة النخبة للخدمات اللوجستية', 'Al-Nokhba Logistics Services Est.', 'Syria', 'Aleppo'],
            ['شركة الرواد للتسويق والتوزيع', 'Al-Ruwad Marketing & Distribution Co.', 'Syria', 'Damascus'],
        ];
        foreach ($trade as $c) {
            $rows[] = $this->buildEntry($c, 'Trade and Services', 'خدمات تجارية ولوجستية شاملة تغطي الاستيراد والتصدير والتوزيع والنقل.', 'Full-service trading and logistics covering import/export, distribution, and freight.');
        }

        // --- Construction (8) ---
        $construction = [
            ['شركة الأساس لمواد البناء', 'Al-Asas Building Materials Co.', 'Syria', 'Damascus'],
            ['مجموعة الحجر والرخام السوري', 'Syrian Stone & Marble Group', 'Syria', 'Homs'],
            ['شركة العمران للمقاولات والإنشاءات', 'Al-Omran Contracting & Construction Co.', 'Syria', 'Damascus'],
            ['مؤسسة البنيان للحلول الإنشائية', 'Al-Bunyan Structural Solutions Est.', 'Syria', 'Aleppo'],
            ['شركة السقف الذهبي للتشطيبات', 'Golden Roof Finishing Co.', 'Jordan', 'Amman'],
            ['مجموعة الإعمار الحديث للمقاولات', 'Modern Reconstruction Group', 'Syria', 'Damascus'],
            ['شركة الخرسانة المتطورة للصناعات الإنشائية', 'Advanced Concrete Construction Industries Co.', 'UAE', 'Dubai'],
            ['مؤسسة الطريق الآمن للبنية التحتية', 'Safe Road Infrastructure Est.', 'Syria', 'Tartus'],
        ];
        foreach ($construction as $c) {
            $rows[] = $this->buildEntry($c, 'Construction', 'مواد بناء وحلول إنشائية وتشطيبات لمشاريع إعادة الإعمار السكنية والتجارية.', 'Building materials, structural solutions, and finishing for residential and commercial reconstruction projects.');
        }

        return $rows;
    }

    private function buildEntry(array $c, string $sector, string $descAr, string $descEn): array
    {
        [$ar, $en, $country, $city] = $c;

        $jobTitles = ['مدير عام', 'الرئيس التنفيذي', 'مدير المبيعات', 'مدير التسويق', 'مدير العلاقات الدولية', 'الشريك المؤسس'];
        $firstNames = ['محمد', 'أحمد', 'خالد', 'سامر', 'رامي', 'ليث', 'ياسمين', 'رنا', 'لينا', 'ديمة', 'نور', 'هبة'];
        $lastNames  = ['العلي', 'الحسن', 'قدور', 'شحادة', 'النجار', 'ديوب', 'حمدان', 'كنعان', 'الأسعد', 'زيدان'];

        $nationalityMap = [
            'Syria' => 'سورية', 'Jordan' => 'أردنية', 'Egypt' => 'مصرية',
            'Saudi Arabia' => 'سعودية', 'Turkey' => 'تركية', 'UAE' => 'إماراتية', 'Lebanon' => 'لبنانية',
        ];

        return [
            'ar' => $ar,
            'en' => $en,
            'sector' => $sector,
            'country' => $country,
            'nationality_ar' => $nationalityMap[$country] ?? $country,
            'address_ar' => "{$city}، شارع المعرض، مبنى {$this->randomBuildingNo()}",
            'address_en' => "{$city}, Exhibition St., Building {$this->randomBuildingNo()}",
            'responsible' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
            'job_title' => $jobTitles[array_rand($jobTitles)],
            'desc_ar' => $descAr,
            'desc_en' => $descEn,
        ];
    }

    private function randomBuildingNo(): int
    {
        return mt_rand(1, 60);
    }

    /**
     * 20 curated events spread across the fair dates, covering all sectors.
     */
    private function eventCatalog(): array
    {
        return [
            ['ar_title' => 'ملتقى الصناعات الغذائية والتصدير', 'en_title' => 'Food Industries & Export Forum', 'sector' => 'Food Industries', 'organizer' => 'اتحاد غرف الصناعة السورية', 'ar_desc' => 'جلسة حوارية حول فرص تصدير المنتجات الغذائية السورية إلى الأسواق الإقليمية.', 'en_desc' => 'A panel on export opportunities for Syrian food products in regional markets.', 'attendance' => 180, 'equipment' => 'شاشة عرض، نظام صوت، منصة متحدثين'],
            ['ar_title' => 'منتدى الاستثمار في الطاقة الشمسية', 'en_title' => 'Solar Energy Investment Forum', 'sector' => 'Renewable Energy', 'organizer' => 'الجمعية السورية للطاقة المتجددة', 'ar_desc' => 'عرض لفرص الاستثمار في مشاريع الطاقة الشمسية وسلاسل التوريد المحلية.', 'en_desc' => 'Investment opportunities in solar projects and local supply chains.', 'attendance' => 150, 'equipment' => 'بروجكتور، طاولات مستديرة'],
            ['ar_title' => 'قمة التحول الرقمي', 'en_title' => 'Digital Transformation Summit', 'sector' => 'Technology', 'organizer' => 'مجموعة نبض للذكاء الاصطناعي', 'ar_desc' => 'مناقشة أدوات الذكاء الاصطناعي وتطبيقاتها في القطاعين العام والخاص.', 'en_desc' => 'Exploring AI tools and their applications across public and private sectors.', 'attendance' => 220, 'equipment' => 'شاشات تفاعلية، إنترنت عالي السرعة'],
            ['ar_title' => 'يوم الاستثمار العقاري والإنشائي', 'en_title' => 'Construction & Real Estate Investment Day', 'sector' => 'Construction', 'organizer' => 'غرفة تجارة دمشق', 'ar_desc' => 'استعراض مشاريع إعادة الإعمار وفرص الشراكة مع شركات المقاولات.', 'en_desc' => 'Showcasing reconstruction projects and partnership opportunities with contractors.', 'attendance' => 200, 'equipment' => 'مجسمات عرض، شاشة كبيرة'],
            ['ar_title' => 'جلسة التوفيق التجاري السوري الخليجي', 'en_title' => 'Syrian-Gulf Business Matchmaking Session', 'sector' => 'Trade and Services', 'organizer' => 'مجلس الأعمال السوري الخليجي', 'ar_desc' => 'لقاءات ثنائية بين رجال أعمال سوريين وخليجيين لبحث فرص الشراكة.', 'en_desc' => 'One-to-one meetings between Syrian and Gulf businesspeople to explore partnerships.', 'attendance' => 90, 'equipment' => 'طاولات لقاءات ثنائية'],
            ['ar_title' => 'ورشة الذكاء الاصطناعي في الصناعة', 'en_title' => 'AI in Industry Workshop', 'sector' => 'Industry & Technology', 'organizer' => 'مؤسسة اليرموك للتصنيع الدقيق', 'ar_desc' => 'ورشة تطبيقية حول أتمتة خطوط الإنتاج باستخدام الذكاء الاصطناعي.', 'en_desc' => 'Hands-on workshop on automating production lines with AI.', 'attendance' => 100, 'equipment' => 'حواسيب محمولة، بروجكتور'],
            ['ar_title' => 'معرض التراث الغذائي السوري', 'en_title' => 'Syrian Culinary Heritage Showcase', 'sector' => 'Food Industries', 'organizer' => 'شركة الأمل للمخابز والمعجنات', 'ar_desc' => 'تذوق وعرض للمنتجات الغذائية التراثية من مختلف المحافظات.', 'en_desc' => 'Tasting and display of traditional food products from different governorates.', 'attendance' => 300, 'equipment' => 'طاولات عرض، تبريد'],
            ['ar_title' => 'هاكاثون الشباب للطاقة المتجددة', 'en_title' => 'Renewable Energy Youth Hackathon', 'sector' => 'Renewable Energy', 'organizer' => 'مجموعة الاستدامة للطاقة الخضراء', 'ar_desc' => 'مسابقة للشباب لابتكار حلول طاقة متجددة منخفضة التكلفة.', 'en_desc' => 'A youth competition to design low-cost renewable energy solutions.', 'attendance' => 120, 'equipment' => 'طاولات عمل، إنترنت، شاشات'],
            ['ar_title' => 'أمسية التواصل التجاري واللوجستي', 'en_title' => 'Trade & Logistics Networking Evening', 'sector' => 'Trade and Services', 'organizer' => 'شركة اللوجستيات المتحدة للخدمات', 'ar_desc' => 'أمسية تواصل بين شركات النقل والتوزيع والتجار المحليين.', 'en_desc' => 'A networking evening for freight, distribution, and local traders.', 'attendance' => 130, 'equipment' => 'إضاءة مسرحية، نظام صوت'],
            ['ar_title' => 'ندوة التصنيع الذكي', 'en_title' => 'Smart Manufacturing Panel', 'sector' => 'Industry & Technology', 'organizer' => 'شركة قاسيون للصناعات الهندسية', 'ar_desc' => 'نقاش حول تحديث خطوط الإنتاج الصناعية بأنظمة إنترنت الأشياء.', 'en_desc' => 'A discussion on modernizing production lines with IoT systems.', 'attendance' => 110, 'equipment' => 'بروجكتور، ميكروفونات'],
            ['ar_title' => 'ندوة اعتماد المباني الخضراء', 'en_title' => 'Green Building Certification Seminar', 'sector' => 'Construction', 'organizer' => 'مجموعة الإعمار الحديث للمقاولات', 'ar_desc' => 'شرح لمعايير اعتماد المباني الخضراء وتطبيقها في مشاريع محلية.', 'en_desc' => 'Explaining green-building certification standards and local project applications.', 'attendance' => 95, 'equipment' => 'شاشة عرض'],
            ['ar_title' => 'حديث حول التقنية المالية والمدفوعات الرقمية', 'en_title' => 'Fintech & Digital Payments Talk', 'sector' => 'Technology', 'organizer' => 'شركة الاتصالات الحديثة للتقنية', 'ar_desc' => 'استعراض حلول الدفع الرقمي وتحديات تبنيها في السوق المحلية.', 'en_desc' => 'Reviewing digital payment solutions and local adoption challenges.', 'attendance' => 140, 'equipment' => 'شاشة، نظام صوت'],
            ['ar_title' => 'عيادة جاهزية التصدير', 'en_title' => 'Export Readiness Clinic', 'sector' => 'Trade and Services', 'organizer' => 'مؤسسة الأندلس للتوزيع والتجارة', 'ar_desc' => 'استشارات مباشرة للشركات الراغبة في التصدير للمرة الأولى.', 'en_desc' => 'One-on-one consultations for companies exporting for the first time.', 'attendance' => 70, 'equipment' => 'طاولات استشارة'],
            ['ar_title' => 'ندوة المرأة في الصناعة والتقنية', 'en_title' => 'Women in Tech & Industry Panel', 'sector' => 'Technology', 'organizer' => 'مجموعة الشبكة للحلول الرقمية', 'ar_desc' => 'جلسة حوارية مع رائدات أعمال في قطاعي التقنية والصناعة.', 'en_desc' => 'A panel discussion with women entrepreneurs in tech and industry.', 'attendance' => 160, 'equipment' => 'ميكروفونات، شاشة عرض'],
            ['ar_title' => 'منتدى فرص الاستثمار في سوريا', 'en_title' => 'Investment Opportunities in Syria Forum', 'sector' => 'Trade and Services', 'organizer' => 'هيئة الاستثمار السورية', 'ar_desc' => 'عرض شامل لفرص الاستثمار في قطاعات الصناعة والطاقة والبنية التحتية.', 'en_desc' => 'A broad overview of investment opportunities in industry, energy, and infrastructure.', 'attendance' => 250, 'equipment' => 'مسرح مؤتمرات، ترجمة فورية', 'is_special' => true],
            ['ar_title' => 'ورشة معايير سلامة الغذاء والجودة', 'en_title' => 'Food Safety & Quality Standards Workshop', 'sector' => 'Food Industries', 'organizer' => 'مجموعة النخبة للزيوت النباتية', 'ar_desc' => 'ورشة تدريبية حول معايير الهاسب وسلامة الغذاء للمصنعين.', 'en_desc' => 'A training workshop on HACCP and food-safety standards for manufacturers.', 'attendance' => 85, 'equipment' => 'حقائب تدريبية، بروجكتور'],
            ['ar_title' => 'معرض ابتكارات مواد البناء', 'en_title' => 'Construction Materials Innovation Showcase', 'sector' => 'Construction', 'organizer' => 'شركة الأساس لمواد البناء', 'ar_desc' => 'عرض لأحدث مواد وتقنيات البناء الموفرة للطاقة.', 'en_desc' => 'A showcase of the latest energy-efficient building materials and techniques.', 'attendance' => 175, 'equipment' => 'طاولات عرض منتجات'],
            ['ar_title' => 'عرض حلول تخزين الطاقة', 'en_title' => 'Energy Storage Solutions Demo', 'sector' => 'Renewable Energy', 'organizer' => 'شركة المتوسط للطاقة الشمسية والتخزين', 'ar_desc' => 'عرض عملي لأنظمة تخزين الطاقة وبطاريات الليثيوم للمشاريع الشمسية.', 'en_desc' => 'A live demo of energy-storage systems and lithium batteries for solar projects.', 'attendance' => 100, 'equipment' => 'عرض تقني، مولد كهربائي احتياطي'],
            ['ar_title' => 'جلسة توعية بالأمن السيبراني', 'en_title' => 'Cybersecurity Awareness Session', 'sector' => 'Technology', 'organizer' => 'شركة الشبكة الآمنة للأمن السيبراني', 'ar_desc' => 'توعية الشركات الصغيرة والمتوسطة بمخاطر الأمن السيبراني وطرق الحماية.', 'en_desc' => 'Raising SME awareness of cybersecurity risks and protection methods.', 'attendance' => 90, 'equipment' => 'شاشة عرض، إنترنت'],
            ['ar_title' => 'حفل الختام وتوزيع الجوائز', 'en_title' => 'Closing Ceremony & Awards Night', 'sector' => 'Trade and Services', 'organizer' => 'إدارة معرض دمشق الدولي', 'ar_desc' => 'حفل ختامي لتكريم أفضل الأجنحة والمشاركين في دورة هذا العام.', 'en_desc' => 'A closing ceremony honoring the best pavilions and participants of this year\'s edition.', 'attendance' => 400, 'equipment' => 'مسرح رئيسي، إضاءة وصوت احترافي', 'is_special' => true],
        ];
    }
}
