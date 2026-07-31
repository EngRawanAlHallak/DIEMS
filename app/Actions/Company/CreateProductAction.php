<?php
namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Jobs\Company\UploadProductImagesToS3;
use App\Models\Product;
use App\Services\ProductService;

class CreateProductAction extends BaseAction
{
    private $translateAction;

    public function __construct(
        protected ProductService $productService,
        TranslateTextAction $translateAction
    )
    {
        $this->translateAction = $translateAction;
    }

    public function execute(array $data, ?array $images = [], $company): Product
    {
        $allowedBooths = ['Kiosk AB', 'Kiosk CD'];
        if (isset($data['price']) && !in_array($company->booth_type, $allowedBooths)) {
            throw new \Exception('لا يمكنك إضافة سعر للمنتجات، نوع حجزك لا يدعم البيع.', 403);
        }

        $translatedName = $this->translateAction->execute($data['name']);
        $translatedDescription = isset($data['description']) ? $this->translateAction->execute($data['description']) : null;

        return $this->executeAction(function () use ($data, $images, $company, $translatedName, $translatedDescription) {
            $data['company_id'] = $company->id;
            $data['name'] = $translatedName;
            $data['description'] = $translatedDescription;

            $product = Product::create($data);

            if (!empty($images)) {
                $tempPaths = array_map(fn($img) => $img->store('temp', 'local'), $images);
                UploadProductImagesToS3::dispatch($product, $tempPaths);
            }

            return $product;
        }, [
            'ar' => "تم إضافة منتج جديد: " . ($translatedName['ar'] ?? ''),
            'en' => "A new product has been added: " . ($translatedName['en'] ?? '')
        ], ['event_type' => 'product_creation'], true);
    }
}
