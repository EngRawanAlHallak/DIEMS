<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\Hall;
use App\Models\Company;
use App\Models\CompanyRequest;
use App\Models\EventRequest;
use App\Models\Booth;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Promotion;
use App\Models\TicketType;
use App\Models\TicketOrder;
use App\Models\Ticket;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ExhibitionOperationsSeeder
 * -----------------------------------------------------------------------
 * Builds on top of ExhibitionDemoSeeder (must run AFTER it). Reads the
 * already-seeded sectors / halls / companies / company_requests /
 * event_requests from the DB (does not regenerate them) and adds:
 *
 *  - hall_sector           : which halls belong to which sector
 *  - booths                : sized so a hall's booths never exceed the
 *                            hall's total_area_sqm; for every APPROVED
 *                            company_request whose payment_status is
 *                            'paid' OR 'partial_paid' we create 2-3
 *                            duplicate "candidate" booths (same sector/
 *                            equipment type/size) so a "find suitable
 *                            booths" query returns more than one match,
 *                            then we run the same matching logic as
 *                            AutoAssignBoothsAction (extended to also
 *                            cover partial_paid, per your instruction
 *                            that every approved+paid-something request
 *                            must end up with a reserved booth) to
 *                            actually assign exactly one of them. The
 *                            rest stay available and unowned, plus extra
 *                            generic filler booths per hall.
 *  - products + images     : ~8-12 products per company (every company,
 *                            since a Company row only exists for an
 *                            approved request)
 *  - promotions             : only for companies whose ASSIGNED booth is
 *                            a 'sales' booth -> 2 discount + 2 bundle
 *                            promotions, each linked to a few of that
 *                            company's own products
 *  - ticket_types            : فردي / زوجية / عائلية(5) / VIP / رجال أعمال / طلابية
 *  - ticket_orders + tickets: ~10 orders per ticket type, payment status
 *                            mostly 'paid' with the rest left 'pending'
 *                            past their expires_at (i.e. effectively
 *                            expired reservations, since the schema's
 *                            enum has no literal "expired" state)
 *  - payments (polymorphic) : one row per amount actually paid - for
 *                            company_requests (paid_amount > 0), for
 *                            event_requests (payment_status=paid and
 *                            total_price > 0), and for ticket_orders
 *                            (payment_status=paid). Nothing is created
 *                            for unpaid records.
 *
 * SAFETY NOTE: visitor_name/email/phone for tickets follow the same
 * pattern as the previous seeder - example.com/.net/.org emails and
 * syntactically-valid-but-fake Syrian phone numbers. See the previous
 * seeder's README for why.
 *
 * ASSUMPTIONS TO VERIFY IN YOUR APP (see bottom of this docblock too):
 *  - Eloquent models exist at App\Models\{Booth,Product,ProductImage,
 *    Promotion,TicketType,TicketOrder,Ticket,Payment} matching the
 *    migrations you shared, with jsonb/array casts already configured
 *    for: products.name/description, ticket_types.name/description,
 *    event/company jsonb fields (already assumed in the previous seeder).
 *  - `payments.payable_type` is written as the model's default FQCN
 *    (e.g. "App\Models\CompanyRequest"). If you've defined a
 *    Relation::morphMap() in a service provider, change PAYABLE_TYPES
 *    below to use your morph aliases instead.
 *  - Currency on payments defaults to your migration's default ('SAR').
 *    Adjust CURRENCY below if your real currency differs.
 *
 * Run with: php artisan db:seed --class=ExhibitionOperationsSeeder
 */
class ExhibitionOperationsSeeder extends Seeder
{
    private const CURRENCY = 'SAR';

    /** @var array<string,int> sector "en" name => id, read from DB */
    private array $sectorIds = [];

    /** @var array<int,array{id:int,code:string,remaining:float}> hall_id => struct, per sector */
    private array $sectorHalls = [];

    /** @var array<string,int> hall code (e.g. "H1") => hall id */
    private array $hallCodeToId = [];

    private const EQUIPMENT_TYPES = ['Equipped Booth', 'Not Equipped Booth', 'Row Space Only', 'Kiosk AB', 'Kiosk CD'];

    /** Which halls (by code) belong to which sector (by English name). */
    private const HALL_SECTOR_MAP = [
        'Food Industries'       => ['H1', 'H4', 'H5', 'H6', 'H31', 'H32', 'H1.1'],
        'Industry & Technology' => ['H2', 'H7', 'H8', 'H9', 'H33', 'H37', 'H10.1'],
        'Renewable Energy'      => ['H3', 'H13', 'H14', 'H15', 'H38', 'H39'],
        'Technology'            => ['H10', 'H16', 'H17', 'H18', 'H40', 'H41'],
        'Trade and Services'    => ['H11', 'H20', 'H21', 'H22', 'H23', 'H42', 'H43', 'VIP'],
        'Construction'          => ['H12', 'H25', 'H26', 'H27', 'H28', 'H29', 'H30', 'H34', 'H35', 'H36', 'H45', 'H46', 'PR'],
    ];

    public function run(): void
    {
        mt_srand(20260822 + 1);

        $this->loadSectorsAndHalls();

        DB::transaction(function () {
            $this->seedHallSector();
            $assignedBoothTypeByCompanyId = $this->seedBooths();
            $this->seedProductsAndPromotions($assignedBoothTypeByCompanyId);
            $ticketTypeIds = $this->seedTicketTypes();
            $this->seedTicketOrdersAndTickets($ticketTypeIds);
            $this->seedPayments();
        });

        $this->command?->info('Operations data seeded: hall_sector, booths, products, promotions, ticket types/orders/tickets, payments.');
    }

    /* ---------------------------------------------------------------- */
    /*  Load existing sectors & halls                                    */
    /* ---------------------------------------------------------------- */

    private function loadSectorsAndHalls(): void
    {
        foreach (DB::table('sectors')->select('id', 'name')->get() as $row) {
            $decoded = $this->decodeJsonMaybeTwice($row->name);
            if (!empty($decoded['en'])) {
                $this->sectorIds[$decoded['en']] = $row->id;
            }
        }

        foreach (DB::table('halls')->select('id', 'name')->get() as $row) {
            $decoded = $this->decodeJsonMaybeTwice($row->name);
            $en = $decoded['en'] ?? '';
            $code = null;
            if (str_starts_with($en, 'Hall ')) {
                $code = substr($en, 5);
            } elseif ($en === 'VIP Pavilion') {
                $code = 'VIP';
            } elseif ($en === 'PR Hall') {
                $code = 'PR';
            }
            if ($code !== null) {
                $this->hallCodeToId[$code] = $row->id;
            }
        }

        $areasById = DB::table('halls')->pluck('total_area_sqm', 'id');

        foreach (self::HALL_SECTOR_MAP as $sectorEn => $codes) {
            $sectorId = $this->sectorIds[$sectorEn] ?? null;
            if (!$sectorId) {
                continue;
            }
            $this->sectorHalls[$sectorId] = [];
            foreach ($codes as $code) {
                if (!isset($this->hallCodeToId[$code])) {
                    continue; // hall wasn't found (shouldn't normally happen)
                }
                $hallId = $this->hallCodeToId[$code];
                $this->sectorHalls[$sectorId][] = [
                    'id'        => $hallId,
                    'code'      => $code,
                    'remaining' => (float) ($areasById[$hallId] ?? 0),
                ];
            }
        }
    }

    private function decodeJsonMaybeTwice($raw): ?array
    {
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        return is_array($decoded) ? $decoded : null;
    }

    /* ---------------------------------------------------------------- */
    /*  hall_sector pivot                                                */
    /* ---------------------------------------------------------------- */

    private function seedHallSector(): void
    {
        $rows = [];
        foreach ($this->sectorHalls as $sectorId => $halls) {
            foreach ($halls as $h) {
                $rows[] = ['hall_id' => $h['id'], 'sector_id' => $sectorId];
            }
        }
        if ($rows) {
            DB::table('hall_sector')->insertOrIgnore($rows);
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Booths: candidates for every approved+paid request + fillers,    */
    /*  then a real auto-assignment pass identical to AutoAssignBoothsAction */
    /* ---------------------------------------------------------------- */

    private function seedBooths(): array
    {
        $boothCounters = []; // hall_id => next booth sequence number

        // 1) Candidate (duplicate) booths for every eligible request: 2-3 identical
        //    (sector + equipment_type + size) booths so more than one match exists.
        $eligible = DB::table('companies as c')
            ->join('company_requests as cr', 'cr.company_id', '=', 'c.id')
            ->where('cr.request_status', 'approved')
            ->whereIn('cr.payment_status', ['paid', 'partial_paid'])
            ->select('c.id as company_id', 'c.sector_id', 'c.booth_type as equipment_type', 'c.final_area')
            ->get();

        $companySalesFlag = []; // company_id => 'sales'|'display', fixed per company so every candidate booth agrees

        foreach ($eligible as $row) {
            $halls = $this->sectorHalls[$row->sector_id] ?? [];
            if (!$halls) {
                continue;
            }
            $companySalesFlag[$row->company_id] = mt_rand(0, 1) === 1 ? 'sales' : 'display';
            $duplicates = mt_rand(2, 3);

            for ($i = 0; $i < $duplicates; $i++) {
                $hallIndex = $this->pickHallWithCapacity($this->sectorHalls[$row->sector_id], (float) $row->final_area);
                if ($hallIndex === null) {
                    continue; // no hall in this sector has room left, skip this duplicate
                }
                $hallId = $this->sectorHalls[$row->sector_id][$hallIndex]['id'];
                $this->sectorHalls[$row->sector_id][$hallIndex]['remaining'] -= (float) $row->final_area;

                $seq = ($boothCounters[$hallId] ??= 0) + 1;
                $boothCounters[$hallId] = $seq;
                $code = $this->sectorHalls[$row->sector_id][$hallIndex]['code'];

                Booth::create([
                    'booth_number'        => sprintf('%s-%02d', $code, $seq),
                    'booth_type'          => $companySalesFlag[$row->company_id],
                    'equipment_type'      => $row->equipment_type,
                    'size_sqm'            => $row->final_area,
                    'available'           => true,
                    'sector_id'           => $row->sector_id,
                    'company_id'          => null,
                    'hall_id'             => $hallId,
                    'company_request_id'  => null,
                ]);
            }
        }

        // 2) Generic filler booths so each hall's booths get close to (but never over) its total area.
        foreach ($this->sectorHalls as $sectorId => &$halls) {
            foreach ($halls as &$hall) {
                $attempts = 0;
                while ($hall['remaining'] >= 15 && $attempts < 6) {
                    $attempts++;
                    $size = min($hall['remaining'], mt_rand(15, 100));
                    $equipmentType = self::EQUIPMENT_TYPES[array_rand(self::EQUIPMENT_TYPES)];

                    $seq = ($boothCounters[$hall['id']] ??= 0) + 1;
                    $boothCounters[$hall['id']] = $seq;

                    Booth::create([
                        'booth_number'        => sprintf('%s-%02d', $hall['code'], $seq),
                        'booth_type'          => mt_rand(0, 1) === 1 ? 'sales' : 'display',
                        'equipment_type'      => $equipmentType,
                        'size_sqm'            => $size,
                        'available'           => true,
                        'sector_id'           => $sectorId,
                        'company_id'          => null,
                        'hall_id'             => $hall['id'],
                        'company_request_id'  => null,
                    ]);
                    $hall['remaining'] -= $size;
                }
            }
            unset($hall);
        }
        unset($halls);

        // 3) Real assignment pass - same matching rule as AutoAssignBoothsAction, extended to
        //    also cover 'partial_paid' (per your instruction every approved+paid-something
        //    request must end up with a reserved booth): exact sector + equipment_type + size
        //    match, ordered by area desc then oldest request first.
        $requests = CompanyRequest::query()
            ->whereNotNull('company_id')
            ->where('request_status', 'approved')
            ->whereIn('payment_status', ['paid', 'partial_paid'])
            ->orderByDesc('requested_area')
            ->orderBy('created_at')
            ->get();

        $assignedBoothTypeByCompanyId = [];

        foreach ($requests as $request) {
            $company = Company::find($request->company_id);
            if (!$company) {
                continue;
            }

            $booth = Booth::query()
                ->where('sector_id', $company->sector_id)
                ->where('equipment_type', $request->setup_preference)
                ->where('size_sqm', (float) $request->requested_area)
                ->where('available', true)
                ->inRandomOrder()
                ->first();

            if ($booth) {
                $booth->update([
                    'available'          => false,
                    'company_id'         => $company->id,
                    'company_request_id' => $request->id,
                ]);
                $assignedBoothTypeByCompanyId[$company->id] = $booth->booth_type;
            }
            // if no booth matched, this request is simply left unassigned - same as the
            // real action's "failed_requests" outcome, which is a realistic scenario to keep.
        }

        return $assignedBoothTypeByCompanyId;
    }

    private function pickHallWithCapacity(array $halls, float $size): ?int
    {
        $bestIndex = null;
        $bestRemaining = -1;
        foreach ($halls as $i => $h) {
            if ($h['remaining'] >= $size && $h['remaining'] > $bestRemaining) {
                $bestRemaining = $h['remaining'];
                $bestIndex = $i;
            }
        }
        return $bestIndex;
    }

    /* ---------------------------------------------------------------- */
    /*  Products, images, promotions                                    */
    /* ---------------------------------------------------------------- */

    private function seedProductsAndPromotions(array $assignedBoothTypeByCompanyId): void
    {
        $pool = $this->productCatalogBySector();

        foreach (Company::all() as $company) {
            $items = $pool[$company->sector] ?? $pool['Trade and Services'];
            $count = mt_rand(8, 12);
            $productIds = [];

            for ($i = 0; $i < $count; $i++) {
                $item = $items[$i % count($items)];
                $slug = Str::slug($item['en']) . '-' . $company->id . '-' . $i;
                $price = round(mt_rand(500, 50000) / 100, 2);

                $product = Product::create([
                    'company_id'  => $company->id,
                    'name'        => ['ar' => $item['ar'], 'en' => $item['en']],
                    'description' => [
                        'ar' => "منتج مقدم من " . $this->jsonExtractAr($company->name) . " - " . $item['ar'] . ".",
                        'en' => "A product offered by " . $this->jsonExtractEn($company->name) . " - " . $item['en'] . ".",
                    ],
                    'price'       => $price,
                ]);
                $productIds[] = $product->id;

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => "https://picsum.photos/seed/{$slug}/600/600",
                    'is_primary' => true,
                ]);
            }

            // Promotions only for companies whose ASSIGNED booth is a 'sales' booth.
            if (($assignedBoothTypeByCompanyId[$company->id] ?? null) === 'sales' && count($productIds) >= 2) {
                $this->createPromotionsForCompany($company->id, $productIds);
            }
        }
    }

    private function createPromotionsForCompany(int $companyId, array $productIds): void
    {
        $start = Carbon::create(2026, 8, 26, 0, 0, 0);
        $end   = Carbon::create(2026, 9, 4, 23, 59, 59);

        // 2 discount promotions
        for ($i = 0; $i < 2; $i++) {
            $promotion = Promotion::create([
                'company_id'           => $companyId,
                'type'                 => 'discount',
                'discount_percentage'  => [10, 15, 20, 25, 30, 40][array_rand([10, 15, 20, 25, 30, 40])],
                'total_package_price'  => null,
                'start_date'           => $start,
                'end_date'             => $end,
                'is_active'            => true,
            ]);
            $picked = (array) array_rand(array_flip($productIds), min(mt_rand(1, 2), count($productIds)));
            foreach ($picked as $productId) {
                DB::table('promotion_products')->insert([
                    'promotion_id' => $promotion->id,
                    'product_id'   => $productId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // 2 bundle promotions
        for ($i = 0; $i < 2; $i++) {
            $bundleCount = min(mt_rand(2, 3), count($productIds));
            $picked = (array) array_rand(array_flip($productIds), $bundleCount);
            $sum = Product::whereIn('id', $picked)->sum('price');
            $packagePrice = round($sum * (mt_rand(70, 85) / 100), 2);

            $promotion = Promotion::create([
                'company_id'           => $companyId,
                'type'                 => 'bundle',
                'discount_percentage'  => null,
                'total_package_price'  => $packagePrice,
                'start_date'           => $start,
                'end_date'             => $end,
                'is_active'            => true,
            ]);
            foreach ($picked as $productId) {
                DB::table('promotion_products')->insert([
                    'promotion_id' => $promotion->id,
                    'product_id'   => $productId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    private function jsonExtractAr($value): string
    {
        $d = is_array($value) ? $value : $this->decodeJsonMaybeTwice($value);
        return $d['ar'] ?? '';
    }

    private function jsonExtractEn($value): string
    {
        $d = is_array($value) ? $value : $this->decodeJsonMaybeTwice($value);
        return $d['en'] ?? '';
    }

    /* ---------------------------------------------------------------- */
    /*  Ticket types, orders, tickets                                    */
    /* ---------------------------------------------------------------- */

    private function seedTicketTypes(): array
    {
        $types = [
            ['ar' => 'فردية', 'en' => 'Individual', 'persons' => 1, 'price' => 15.00],
            ['ar' => 'زوجية', 'en' => 'Couple', 'persons' => 2, 'price' => 25.00],
            ['ar' => 'عائلية (5 أشخاص)', 'en' => 'Family (5 persons)', 'persons' => 5, 'price' => 55.00],
            ['ar' => 'VIP', 'en' => 'VIP', 'persons' => 1, 'price' => 100.00],
            ['ar' => 'رجال أعمال', 'en' => 'Business', 'persons' => 1, 'price' => 75.00],
            ['ar' => 'طلابية', 'en' => 'Student', 'persons' => 1, 'price' => 8.00],
        ];

        $ids = [];
        foreach ($types as $t) {
            $ticketType = TicketType::create([
                'name'         => ['ar' => $t['ar'], 'en' => $t['en']],
                'description'  => [
                    'ar' => "تذكرة {$t['ar']} تسمح بدخول {$t['persons']} أشخاص.",
                    'en' => "A {$t['en']} ticket admitting {$t['persons']} person(s).",
                ],
                'price'         => $t['price'],
                'persons_count' => $t['persons'],
                'is_active'     => true,
            ]);
            $ids[$t['en']] = ['id' => $ticketType->id, 'price' => $t['price']];
        }
        return $ids;
    }

    private function seedTicketOrdersAndTickets(array $ticketTypeIds): void
    {
        $firstNames = ['محمد', 'أحمد', 'خالد', 'سامر', 'رامي', 'ليث', 'ياسمين', 'رنا', 'لينا', 'ديمة', 'نور', 'هبة', 'عمر', 'زينب', 'مريم'];
        $lastNames  = ['العلي', 'الحسن', 'قدور', 'شحادة', 'النجار', 'ديوب', 'حمدان', 'كنعان', 'الأسعد', 'زيدان'];
        $interests  = ['تكنولوجيا', 'أغذية', 'بناء', 'صناعة وإنتاج', 'طاقة واستدامة', 'تجارة وخدمات'];

        $fairStart = Carbon::create(2026, 8, 26, 17, 0, 0);
        $fairEnd   = Carbon::create(2026, 9, 4, 23, 0, 0);

        foreach ($ticketTypeIds as $en => $info) {
            $ordersForType = 10;
            $paidCount = 7; // majority paid
            $statuses = array_merge(array_fill(0, $paidCount, 'paid'), array_fill(0, $ordersForType - $paidCount, 'pending'));
            shuffle($statuses);

            foreach ($statuses as $i => $status) {
                $quantity = mt_rand(1, 2);
                $totalAmount = $info['price'] * $quantity;

                if ($status === 'paid') {
                    $createdAt = Carbon::now()->subDays(mt_rand(0, 5));
                    $expiresAt = null;
                } else {
                    // Represents an "expired" reservation: never paid, and its hold window has passed.
                    $createdAt = Carbon::now()->subDays(mt_rand(2, 6));
                    $expiresAt = (clone $createdAt)->addHours(2);
                }

                $order = TicketOrder::create([
                    'uuid'           => (string) Str::uuid(),
                    'guest_id'       => 'guest_' . Str::random(24),
                    'total_amount'   => $totalAmount,
                    'payment_status' => $status,
                    'expires_at'     => $expiresAt,
                    'created_at'     => $createdAt,
                    'updated_at'     => $createdAt,
                ]);

                for ($t = 0; $t < $quantity; $t++) {
                    $visitorName = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
                    $slug = Str::slug($visitorName) . '-' . $order->id . '-' . $t;

                    if ($status === 'paid') {
                        $ticketStatus = mt_rand(0, 1) === 1 ? 'used' : 'valid';
                        $usedAt = $ticketStatus === 'used'
                            ? Carbon::createFromTimestamp(mt_rand($fairStart->timestamp, $fairEnd->timestamp))
                            : null;
                    } else {
                        $ticketStatus = 'cancelled';
                        $usedAt = null;
                    }

                    Ticket::create([
                        'uuid'            => (string) Str::uuid(),
                        'ticket_order_id' => $order->id,
                        'ticket_type_id'  => $info['id'],
                        'visitor_name'    => $visitorName,
                        'visitor_email'   => $slug . '@' . $this->demoDomain($order->id + $t),
                        'visitor_phone'   => $this->syrianPhone(),
                        'interest_field'  => $interests[array_rand($interests)],
                        'status'          => $ticketStatus,
                        'used_at'         => $usedAt,
                    ]);
                }
            }
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Payments (polymorphic)                                           */
    /* ---------------------------------------------------------------- */

    private function seedPayments(): void
    {
        // Company requests: one payment per amount actually paid (covers both 'paid' and 'partial_paid').
        foreach (CompanyRequest::where('paid_amount', '>', 0)->get() as $request) {
            $this->createPayment(CompanyRequest::class, $request->id, (float) $request->paid_amount, $request->created_at);
        }

        // Event requests: only when actually paid AND there was a real price.
        foreach (EventRequest::where('payment_status', 'paid')->where('total_price', '>', 0)->get() as $event) {
            $this->createPayment(EventRequest::class, $event->id, (float) $event->total_price, $event->created_at);
        }

        // Ticket orders: only the paid ones.
        foreach (TicketOrder::where('payment_status', 'paid')->get() as $order) {
            $this->createPayment(TicketOrder::class, $order->id, (float) $order->total_amount, $order->created_at);
        }
    }

    private function createPayment(string $payableType, int $payableId, float $amount, $createdAt): void
    {
        Payment::create([
            'uuid'                => (string) Str::uuid(),
            'payable_type'        => $payableType,
            'payable_id'          => $payableId,
            'paymera_payment_id'  => 'PMR-' . strtoupper(Str::random(12)),
            'amount'              => $amount,
            'currency'            => self::CURRENCY,
            'status'              => 'paid',
            'payment_url'         => null,
            'expires_at'          => null,
            'gateway_response'    => json_encode([
                'status'    => 'success',
                'reference' => 'REF-' . strtoupper(Str::random(10)),
                'processed_at' => Carbon::parse($createdAt)->toIso8601String(),
            ]),
            'created_at'          => $createdAt,
            'updated_at'          => $createdAt,
        ]);
    }

    /* ---------------------------------------------------------------- */
    /*  Small helpers (mirrors ExhibitionDemoSeeder)                     */
    /* ---------------------------------------------------------------- */

    private function syrianPhone(): string
    {
        $prefixes = ['93', '94', '95', '96', '98', '99'];
        $prefix = $prefixes[array_rand($prefixes)];
        $rest = str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        return '+963' . $prefix . $rest;
    }

    private function demoDomain(int $i): string
    {
        $domains = ['example.com', 'example.net', 'example.org'];
        return $domains[$i % count($domains)];
    }

    /**
     * ~12 plausible products per sector.
     */
    private function productCatalogBySector(): array
    {
        return [
            'Food Industries' => [
                ['ar' => 'جبنة بيضاء', 'en' => 'White Cheese'],
                ['ar' => 'لبنة بالزعتر', 'en' => 'Labneh with Zaatar'],
                ['ar' => 'زيت زيتون بكر', 'en' => 'Extra Virgin Olive Oil'],
                ['ar' => 'مربى المشمش', 'en' => 'Apricot Jam'],
                ['ar' => 'عصير رمان طبيعي', 'en' => 'Natural Pomegranate Juice'],
                ['ar' => 'حلاوة طحينية', 'en' => 'Halva'],
                ['ar' => 'معجون طماطم', 'en' => 'Tomato Paste'],
                ['ar' => 'مخلل مشكل', 'en' => 'Mixed Pickles'],
                ['ar' => 'خبز الصاج', 'en' => 'Saj Bread'],
                ['ar' => 'مكسرات محمصة', 'en' => 'Roasted Nuts Mix'],
                ['ar' => 'عسل جبلي', 'en' => 'Mountain Honey'],
                ['ar' => 'دبس رمان', 'en' => 'Pomegranate Molasses'],
            ],
            'Industry & Technology' => [
                ['ar' => 'مضخة مياه صناعية', 'en' => 'Industrial Water Pump'],
                ['ar' => 'محرك كهربائي', 'en' => 'Electric Motor'],
                ['ar' => 'قالب حقن بلاستيك', 'en' => 'Plastic Injection Mold'],
                ['ar' => 'لوحة كهربائية', 'en' => 'Electrical Control Panel'],
                ['ar' => 'صمام هيدروليكي', 'en' => 'Hydraulic Valve'],
                ['ar' => 'خط تعبئة أوتوماتيكي', 'en' => 'Automatic Filling Line'],
                ['ar' => 'مكبس صناعي', 'en' => 'Industrial Press'],
                ['ar' => 'وحدة تبريد صناعية', 'en' => 'Industrial Cooling Unit'],
                ['ar' => 'قطع غيار معدنية', 'en' => 'Metal Spare Parts'],
                ['ar' => 'ذراع لحام آلي', 'en' => 'Welding Robot Arm'],
                ['ar' => 'ناقل حزامي', 'en' => 'Conveyor Belt System'],
                ['ar' => 'مولد كهربائي', 'en' => 'Power Generator Set'],
            ],
            'Renewable Energy' => [
                ['ar' => 'لوح شمسي 450 واط', 'en' => '450W Solar Panel'],
                ['ar' => 'إنفرتر شمسي هجين', 'en' => 'Hybrid Solar Inverter'],
                ['ar' => 'بطارية ليثيوم للتخزين', 'en' => 'Lithium Storage Battery'],
                ['ar' => 'حامل ألمنيوم للألواح', 'en' => 'Aluminum Panel Mounting Frame'],
                ['ar' => 'منظم شحن شمسي', 'en' => 'Solar Charge Controller'],
                ['ar' => 'مضخة مياه شمسية', 'en' => 'Solar Water Pump'],
                ['ar' => 'كابل شمسي مقاوم للأشعة', 'en' => 'UV-Resistant Solar Cable'],
                ['ar' => 'نظام إنارة شمسية للشوارع', 'en' => 'Solar Street Lighting System'],
                ['ar' => 'عاكس طاقة صغير للمنازل', 'en' => 'Home Micro Inverter'],
                ['ar' => 'محطة شحن سيارات كهربائية', 'en' => 'EV Charging Station'],
                ['ar' => 'سخان مياه شمسي', 'en' => 'Solar Water Heater'],
                ['ar' => 'توربين رياح صغير', 'en' => 'Small Wind Turbine'],
            ],
            'Technology' => [
                ['ar' => 'نظام نقاط بيع', 'en' => 'POS System'],
                ['ar' => 'تطبيق جوال مخصص', 'en' => 'Custom Mobile App'],
                ['ar' => 'لوحة تحكم ذكية', 'en' => 'Smart Dashboard Panel'],
                ['ar' => 'كاميرا مراقبة ذكية', 'en' => 'Smart Surveillance Camera'],
                ['ar' => 'خدمة استضافة سحابية', 'en' => 'Cloud Hosting Service'],
                ['ar' => 'نظام إدارة موارد ERP', 'en' => 'ERP Management System'],
                ['ar' => 'جدار حماية شبكي', 'en' => 'Network Firewall'],
                ['ar' => 'روبوت محادثة ذكاء اصطناعي', 'en' => 'AI Chatbot Solution'],
                ['ar' => 'راوتر شبكي متقدم', 'en' => 'Advanced Network Router'],
                ['ar' => 'نظام حضور وانصراف بصمة', 'en' => 'Biometric Attendance System'],
                ['ar' => 'منصة تجارة إلكترونية', 'en' => 'E-commerce Platform'],
                ['ar' => 'حل النسخ الاحتياطي السحابي', 'en' => 'Cloud Backup Solution'],
            ],
            'Trade and Services' => [
                ['ar' => 'خدمة شحن دولي', 'en' => 'International Freight Service'],
                ['ar' => 'خدمة تخليص جمركي', 'en' => 'Customs Clearance Service'],
                ['ar' => 'عقد توزيع حصري', 'en' => 'Exclusive Distribution Contract'],
                ['ar' => 'خدمة تخزين ومستودعات', 'en' => 'Warehousing & Storage Service'],
                ['ar' => 'باقة استيراد بالجملة', 'en' => 'Bulk Import Package'],
                ['ar' => 'خدمة نقل بري مبرد', 'en' => 'Refrigerated Land Transport'],
                ['ar' => 'وكالة تجارية عامة', 'en' => 'General Trading Agency'],
                ['ar' => 'خدمة تعبئة وتغليف للتصدير', 'en' => 'Export Packaging Service'],
                ['ar' => 'حزمة تأمين شحنات', 'en' => 'Cargo Insurance Package'],
                ['ar' => 'خدمة استشارات تجارية', 'en' => 'Trade Consulting Service'],
                ['ar' => 'عقد توريد سنوي', 'en' => 'Annual Supply Contract'],
                ['ar' => 'خدمة نقل بحري', 'en' => 'Sea Freight Service'],
            ],
            'Construction' => [
                ['ar' => 'بلاط سيراميك فاخر', 'en' => 'Premium Ceramic Tiles'],
                ['ar' => 'رخام كرارة', 'en' => 'Karara Marble'],
                ['ar' => 'أسمنت مقاوم', 'en' => 'Resistant Cement'],
                ['ar' => 'حديد تسليح', 'en' => 'Reinforcement Steel Bars'],
                ['ar' => 'دهانات خارجية عازلة', 'en' => 'Insulating Exterior Paint'],
                ['ar' => 'ألواح جبس بورد', 'en' => 'Gypsum Board Panels'],
                ['ar' => 'أبواب حديد أمان', 'en' => 'Steel Security Doors'],
                ['ar' => 'نوافذ ألمنيوم مزدوجة', 'en' => 'Double-Glazed Aluminum Windows'],
                ['ar' => 'عزل مائي للأسطح', 'en' => 'Waterproofing for Roofs'],
                ['ar' => 'بلوك خرساني خفيف', 'en' => 'Lightweight Concrete Block'],
                ['ar' => 'كابلات كهربائية للمباني', 'en' => 'Building Electrical Cables'],
                ['ar' => 'خلاطات باطون جاهزة', 'en' => 'Ready-Mix Concrete'],
            ],
        ];
    }
}
