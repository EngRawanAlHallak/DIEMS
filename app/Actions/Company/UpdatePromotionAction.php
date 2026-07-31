<?php

namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Models\Promotion;

class UpdatePromotionAction extends BaseAction
{
    public function execute(Promotion $promotion, array $data): Promotion
    {
        return $this->executeAction(function () use ($promotion, $data) {
            if ($promotion->type === 'discount') {
                if (isset($data['discount_percentage'])) {
                    $promotion->discount_percentage = $data['discount_percentage'];
                }
            } elseif ($promotion->type === 'bundle') {
                if (isset($data['total_package_price'])) {
                    $promotion->total_package_price = $data['total_package_price'];
                }
            }
            if (isset($data['start_date'])) $promotion->start_date = $data['start_date'];
            if (isset($data['end_date']))   $promotion->end_date = $data['end_date'];
            if (isset($data['is_active']))  $promotion->is_active = $data['is_active'];

            $promotion->save();
            if (isset($data['product_ids'])) {
                $promotion->products()->sync($data['product_ids']);
            }

            return $promotion;

        }, "تم تحديث تفاصيل العرض بنجاح", ['updated_keys' => array_keys($data)], true);
    }
}
