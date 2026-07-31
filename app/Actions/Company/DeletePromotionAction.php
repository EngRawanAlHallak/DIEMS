<?php

namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Models\Promotion;

class DeletePromotionAction extends BaseAction
{
    /**
     * حذف عرض وكافة ارتباطاته
     */
    public function execute(Promotion $promotion): Promotion
    {
        return $this->executeAction(function () use ($promotion) {

            $promotion->products()->detach();
            $promotion->delete();

            return $promotion;

        }, "تم حذف العرض بنجاح [ID: {$promotion->id}]", [], true);
    }
}
