<?php

namespace App\Jobs\Company;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadProductImagesToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 90;

    public function __construct(
        protected Product $product,
        protected array $tempFilePaths
    ) {}

    public function handle(): void
    {
        foreach ($this->tempFilePaths as $tempPath) {
            if (Storage::disk('local')->exists($tempPath)) {
                $fileContent = Storage::disk('local')->get($tempPath);
                $fileName    = basename($tempPath);
                $s3Path      = "products/images/{$fileName}";

                Storage::disk('s3')->put($s3Path, $fileContent, 'public');

                $this->product->images()->create([
                    'image_path' => $s3Path
                ]);

                Storage::disk('local')->delete($tempPath);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[UploadProductImagesToS3] Job failed', [
            'product_id' => $this->product->id,
            'error'      => $e->getMessage(),
        ]);

        activity('system_error')
            ->performedOn($this->product)
            ->withProperties([
                'job'             => self::class,
                'product_id'      => $this->product->id,
                'temp_file_paths' => $this->tempFilePaths,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
                'failed_at'       => now()->toDateTimeString(),
            ])
            ->log("فشل رفع صور المنتج (#{$this->product->id}) إلى السيرفر: {$e->getMessage()}");
    }
}
