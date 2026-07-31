<?php

namespace App\Jobs\Company;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadCompanyDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        protected $model,
        protected array $tempFiles
    ) {}

    public function handle(): void
    {
        foreach ($this->tempFiles as $fileData) {
            if (Storage::disk('local')->exists($fileData['temp_path'])) {
                $fileContents = Storage::disk('local')->get($fileData['temp_path']);
                $s3Path = 'companies/documents/' . basename($fileData['temp_path']);
                Storage::disk('s3')->put($s3Path, $fileContents, 'private');

                $this->model->documents()->create([
                    'file_path' => $s3Path,
                    'file_type' => $fileData['file_type'],
                ]);

                Storage::disk('local')->delete($fileData['temp_path']);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[UploadCompanyDocuments] Job failed', [
            'model_type' => get_class($this->model),
            'model_id'   => $this->model->getKey(),
            'error'      => $e->getMessage(),
        ]);

        activity('system_error')
            ->performedOn($this->model)
            ->withProperties([
                'job'        => self::class,
                'temp_files' => $this->tempFiles,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                'failed_at'  => now()->toDateTimeString(),
            ])
            ->log("فشل رفع وثائق الشركة المرفقة للموديل (" . class_basename($this->model) . " #{$this->model->getKey()}): {$e->getMessage()}");
    }
}
