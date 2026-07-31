<?php


namespace Database\Seeders;

use App\Models\PricingTier;
use Illuminate\Database\Seeder;

class PricingTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            // ───── مباني مجهزة ─────────────────────────────
            [
                'name'          => 'مبنى مجهز - شركات وطنية',
                'slug'          => 'Equipped Booth',
                'company_type'  => 'local',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 85.00,
                'min_area'      => 12.00,
                'sort_order'    => 1,
                'description'   => 'مبنى مجهز بالكامل للشركات الوطنية',
            ],
            [
                'name'          => 'مبنى مجهز - شركات أجنبية',
                'slug'          => 'Equipped Booth',
                'company_type'  => 'foreign',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 150.00,
                'min_area'      => 12.00,
                'sort_order'    => 2,
                'description'   => 'مبنى مجهز بالكامل للشركات الأجنبية',
            ],

            // ───── مباني غير مجهزة ─────────────────────────
            [
                'name'          => 'مبنى غير مجهز - شركات وطنية',
                'slug'          => 'Not Equipped Booth',
                'company_type'  => 'local',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 70.00,
                'min_area'      => 12.00,
                'sort_order'    => 3,
                'description'   => 'مبنى غير مجهز للشركات الوطنية',
            ],
            [
                'name'          => 'مبنى غير مجهز - شركات أجنبية',
                'slug'          => 'Not Equipped Booth',
                'company_type'  => 'foreign',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 120.00,
                'min_area'      => 12.00,
                'sort_order'    => 4,
                'description'   => 'مبنى غير مجهز للشركات الأجنبية',
            ],

            // ───── مكشوف ───────────────────────────────────
            [
                'name'          => 'مكشوف (مساحة فقط) - شركات وطنية',
                'slug'          => 'Row Space Only',
                'company_type'  => 'local',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 45.00,
                'min_area'      => 50.00,
                'sort_order'    => 5,
                'description'   => 'مساحة مكشوفة للشركات الوطنية، الحد الأدنى 50 متر',
            ],
            [
                'name'          => 'مكشوف - شركات أجنبية',
                'slug'          => 'Row Space Only',
                'company_type'  => 'foreign',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 75.00,
                'min_area'      => 50.00,
                'sort_order'    => 6,
                'description'   => 'مساحة مكشوفة للشركات الأجنبية، الحد الأدنى 50 متر',
            ],

            // ───── أجنحة خاصة (العقود) ─────────────────────
            [
                'name'          => 'جناح مشاد من قبل الشركة',
                'slug'          => 'Private Pavilion',
                'company_type'  => 'any',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 45.00,
                'min_area'      => 12.00,
                'sort_order'    => 7,
                'description'   => 'أجنحة مشادة من قبل الشركات الخاصة وفق العقود',
            ],

            // ───── مركز رجال الأعمال ───────────────────────
            [
                'name'          => 'حجز مركز رجال الأعمال',
                'slug'          => 'Business Center',
                'company_type'  => 'any',
                'pricing_type'  => 'per_day',
                'unit_price'    => 1500.00,
                'min_area'      => null,
                'sort_order'    => 8,
                'description'   => 'حجز مركز رجال الأعمال كاملاً لمدة يوم',
            ],

            // ───── قاعات المحاضرات ─────────────────────────
            [
                'name'          => 'حجز قاعة المحاضرات',
                'slug'          => 'Lecture Hall',
                'company_type'  => 'local',
                'pricing_type'  => 'per_day',
                'unit_price'    => 500.00,
                'min_area'      => null,
                'sort_order'    => 9,
                'description'   => 'حجز قاعة محاضرات لمدة يوم كامل',
            ],

            // ───── أكشاك البيع ─────────────────────────────
            [
                'name'          => 'كشك بيع - مراكز A وB',
                'slug'          => 'Kiosk AB',
                'company_type'  => 'local',
                'pricing_type'  => 'flat_per_period',
                'unit_price'    => 1800.00,
                'period_days'   => 10,
                'location_zone' => 'ab',
                'sort_order'    => 10,
                'description'   => 'كشك بيع في مراكز A وB لمدة 10 أيام',
            ],
            [
                'name'          => 'كشك بيع - مراكز C وD',
                'slug'          => 'Kiosk CD',
                'company_type'  => 'local',
                'pricing_type'  => 'flat_per_period',
                'unit_price'    => 900.00,
                'period_days'   => 10,
                'location_zone' => 'cd',
                'sort_order'    => 11,
                'description'   => 'كشك بيع في مراكز C وD لمدة 10 أيام',
            ],

            // ───── الألعاب ──────────────────────────────────
            [
                'name'          => 'منطقة الألعاب',
                'slug'          => 'Games Area',
                'company_type'  => 'local',
                'pricing_type'  => 'per_sqm',
                'unit_price'    => 2.00,
                'min_area'      => null,
                'sort_order'    => 12,
                'description'   => 'مساحة لمنطقة الألعاب',
            ],
        ];

        foreach ($tiers as $tier) {
            PricingTier::updateOrCreate(
                ['slug' => $tier['slug'],'company_type' => $tier['company_type']],
                array_merge($tier, [
                    'currency'      => 'USD',
                    'period_days'   => $tier['period_days'] ?? null,
                    'location_zone' => $tier['location_zone'] ?? null,
                    'is_active'     => true,
                ])
            );
        }
    }
}
