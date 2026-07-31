<?php

namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Jobs\Company\UploadProductImagesToS3;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductService;
use Illuminate\Support\Facades\Storage;

class UpdateProductAction extends BaseAction
{
    public function __construct(protected ProductService $productService
    ,protected TranslateTextAction $translateAction) {}

    public function execute(Product $product, array $data, $company, ?array $newImages = [], ?array $imagesToDelete = []): Product
    {
        $allowedBooths = ['Kiosk AB', 'Kiosk CD'];
        if (isset($data['price']) && $data['price'] > 0 && !in_array($company->booth_type, $allowedBooths)) {
            throw new \Exception('نوع حجزك لا يدعم إضافة سعر.', 403);
        }

        if (isset($data['name'])) {
            $data['name'] = $this->translateAction->execute($data['name']);
        }
        if (isset($data['description'])) {
            $data['description'] = $this->translateAction->execute($data['description']);
        }

        $logNameAr = $data['name']['ar'] ?? $product->getTranslation('name', 'ar');
        $logNameEn = $data['name']['en'] ?? $product->getTranslation('name', 'en');

        return $this->executeAction(function () use ($product, $data, $newImages, $imagesToDelete) {
            $product->update($data);

            if (!empty($imagesToDelete)) {
                $this->deleteImages($imagesToDelete);
            }

            if (!empty($newImages)) {
                $tempPaths = array_map(fn($img) => $img->store('temp', 'local'), $newImages);
                UploadProductImagesToS3::dispatch($product, $tempPaths);
            }

            return $product->fresh('images');
        }, [
            'ar' => "تم تحديث المنتج: {$logNameAr}",
            'en' => "Product updated: {$logNameEn}"
        ], ['event_type' => 'product_updated'], true);
    }
    /**
     * حذف الصور
     */
    protected function deleteImages(array $imageIds): void
    {
        $images = ProductImage::whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            Storage::disk('s3')->delete($image->image_path);
            $image->delete();
        }
    }
}
