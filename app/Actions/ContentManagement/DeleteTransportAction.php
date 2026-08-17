<?php

namespace App\Actions\ContentManagement;

use App\Actions\General\BaseAction;
use App\Models\Transportation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DeleteTransportAction extends BaseAction
{
    public function execute(Transportation $transport): Transportation
    {
        return $this->executeAction(
            function () use ($transport) {
                if ($transport->image && Storage::disk('s3')->exists($transport->image)) {
                    Storage::disk('s3')->delete($transport->image);
                }

                $model = $transport;
                $transport->delete();

                Cache::forget('transportation_page_ar');
                Cache::forget('transportation_page_en');

                return $model;
            },
            [
                'ar' => 'تم حذف خط المواصلات بنجاح',
                'en' => 'Transport line deleted successfully'
            ],
            [],
            true
        );
    }
}
