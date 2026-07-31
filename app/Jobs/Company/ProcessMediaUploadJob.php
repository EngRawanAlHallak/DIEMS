<?php

namespace App\Jobs\Company;

use App\Actions\Company\UploadMediaToS3Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProcessMediaUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        protected Model $model,
        protected string $attribute,
        protected string $tempLocalPath,
        protected string $s3Folder,
        protected ?string $oldPath = null
    ) {}

    public function handle(UploadMediaToS3Action $uploader): void
    {
        $s3Path = $uploader->execute($this->tempLocalPath, $this->s3Folder, $this->oldPath);

        if (!empty($s3Path)) {
            $this->model->update([
                $this->attribute => $s3Path
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[ProcessMediaUploadJob] Failed to upload media', [
            'model_type' => get_class($this->model),
            'model_id'   => $this->model->getKey(),
            'attribute'  => $this->attribute,
            'error'      => $e->getMessage(),
        ]);

        activity('system_error')
            ->performedOn($this->model)
            ->withProperties([
                'job'             => self::class,
                'attribute'       => $this->attribute,
                'temp_local_path' => $this->tempLocalPath,
                's3_folder'       => $this->s3Folder,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
                'failed_at'       => now()->toDateTimeString(),
            ])
            ->log("فشل رفع الميديا للخاصية ({$this->attribute}) على الموديل (" . class_basename($this->model) . " #{$this->model->getKey()}): {$e->getMessage()}");
    }
}
