<?php

namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Models\Promotion;
use Exception;

class StorePromotionAction extends BaseAction
{
    public function execute(array $data, $company): Promotion
    {
        // 1. تحديد أنواع الأكشاك المسموح لها بالبيع (إضافة عروض)
        $allowedBooths = ['Kiosk AB', 'Kiosk CD'];

        // 2. التحقق من صلاحية البيع بناءً على نوع الكشك
        if (!in_array($company->booth_type, $allowedBooths)) {
            throw new Exception('عذراً، نوع حجزك لا يدعم إضافة عروض تجارية أو بيع مباشر.', 403);
        }

        return $this->executeAction(function () use ($data, $company) {

            // 3. إنشاء سجل العرض
            $promotion = Promotion::create([
                'company_id'          => $company->id,
                'type'                => $data['type'], // 'discount' أو 'bundle'
                'discount_percentage' => $data['type'] === 'discount' ? $data['discount_percentage'] : null,
                'total_package_price' => $data['type'] === 'bundle' ? $data['total_package_price'] : null,
                'start_date'          => $data['start_date'] ?? now(),
                'end_date'            => $data['end_date'] ?? now()->addDays(10), // افتراضياً لنهاية المعرض
                'is_active'           => true,
            ]);

            // 4. ربط المنتجات المختارة بالعرض (الجدول الوسيط)
            if (!empty($data['product_ids'])) {
                $promotion->products()->sync($data['product_ids']);
            }

            // تجهيز معلومات إضافية للـ Log ليكون واضحاً
            $this->customProperties = [
                'promotion_type' => $data['type'],
                'products_count' => count($data['product_ids']),
                'value'          => $data['type'] === 'discount' ? $data['discount_percentage'].'%' : $data['total_package_price']
            ];

            return $promotion;

        }, "تم إضافة عرض جديد من نوع [" . ($data['type'] === 'bundle' ? 'حزمة' : 'خصم') . "]", $this->customProperties ?? [], true);
    }
}
