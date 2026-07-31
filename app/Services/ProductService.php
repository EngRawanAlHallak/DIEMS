<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductService
{
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function addImageToProduct(Product $product, string $path)
    {
        return $product->images()->create([
            'image_path' => $path
        ]);
    }

    public function uploadFile(UploadedFile $file, string $folder): string
    {
        try {
            // التخزين على S3 مع جعل الملف قابل للقراءة (public)
            return Storage::disk('s3')->put($folder, $file, 'public');
        } catch (Throwable $e) {
            activity('system_error')
                ->withProperties([
                    'service'     => self::class,
                    'folder'      => $folder,
                    'file_name'   => $file->getClientOriginalName(),
                    'error'       => $e->getMessage(),
                    'trace'       => $e->getTraceAsString(),
                ])
                ->log("فشل رفع الملف إلى S3: {$e->getMessage()}");

            throw $e; // نعيد رميه ليتعامل معه الكنترولر أو الـ Exception Handler
        }
    }
}
