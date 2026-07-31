<?php

namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class DeleteProductAction extends BaseAction
{
    // غيرنا نوع الإرجاع المتوقع إلى Model أو mixed لأننا سنمرر الكائن للـ Log
    public function execute(Product $product): mixed
    {
        $productNameAr = $product->getTranslation('name', 'ar');
        $productNameEn = $product->getTranslation('name', 'en');

        return $this->executeAction(function () use ($product) {
            // حذف الصور من S3
            foreach ($product->images as $image) {
                Storage::disk('s3')->delete($image->image_path);
            }

            // حذف علاقات الصور من قاعدة البيانات
            $product->images()->delete();

            // تنفيذ الحذف الفعلي للمنتج
            $product->delete();

            // 🔥 السحر هنا: نُرجع كائن الـ product نفسه وليس true/false
            // لكي يستطيع الـ BaseAction التقاطه كـ Model وتسجيل الـ subject_id والـ subject_type في جدول اللوق
            return $product;

        }, [
            'ar' => "تم حذف المنتج: {$productNameAr}",
            'en' => "Product deleted: {$productNameEn}"
        ], ['event_type' => 'product_deletion'], true);
    }
}
