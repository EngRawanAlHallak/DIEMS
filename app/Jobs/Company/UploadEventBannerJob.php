<?php

namespace App\Jobs\Company;

use App\Models\EventRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadEventBannerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected EventRequest $eventRequest;
    protected string $tempPath;

    public function __construct(
        EventRequest $eventRequest,
        string $tempPath
    ) {
        $this->eventRequest = $eventRequest;
        $this->tempPath = $tempPath;
    }

    public function handle(): void
    {
        $localDisk = Storage::disk('local');
        $s3Disk = Storage::disk('s3');

        if (!$localDisk->exists($this->tempPath)) {
            Log::warning(
                'Temporary event image not found',
                [
                    'event_request_id' => $this->eventRequest->id,
                    'temp_path'        => $this->tempPath,
                ]
            );

            return;
        }

        try {

            /*
             * اسم الملف المؤقت
             */
            $fileName = basename($this->tempPath);

            /*
             * Path داخل S3 فقط
             *
             * مثال:
             * events/banners/8_abc123.jpg
             */
            $s3Path = sprintf(
                'events/banners/%d_%s',
                $this->eventRequest->id,
                $fileName
            );

            /*
             * رفع الملف إلى S3
             */
            $s3Disk->put(
                $s3Path,
                $localDisk->get($this->tempPath),
                [
                    'visibility' => 'public',
                ]
            );

            /*
             * مهم جداً:
             *
             * نخزن PATH فقط.
             *
             * لا نستخدم:
             * Storage::disk('s3')->url($s3Path)
             */
            $this->eventRequest->update([
                'image' => $s3Path,
            ]);

            Log::info(
                'Event banner uploaded successfully',
                [
                    'event_request_id' => $this->eventRequest->id,
                    's3_path'          => $s3Path,
                    'db_image'         => $this->eventRequest->fresh()->image,
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'Failed to upload event banner to S3',
                [
                    'event_request_id' => $this->eventRequest->id,
                    'temp_path'        => $this->tempPath,
                    'error'            => $e->getMessage(),
                ]
            );

            throw $e;

        } finally {

            /*
             * حذف النسخة المؤقتة من local
             */
            $localDisk->delete($this->tempPath);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error(
            '[UploadEventBannerJob] Job failed completely',
            [
                'event_request_id' => $this->eventRequest->id,
                'temp_path'        => $this->tempPath,
                'error'            => $e->getMessage(),
            ]
        );

        activity('system_error')
            ->performedOn($this->eventRequest)
            ->withProperties([
                'job'              => self::class,
                'event_request_id' => $this->eventRequest->id,
                'temp_path'        => $this->tempPath,
                'error'            => $e->getMessage(),
                'trace'            => $e->getTraceAsString(),
                'failed_at'        => now()->toDateTimeString(),
            ])
            ->log(
                "فشل رفع بنر الفعالية الخاصة بطلب الفعالية رقم (#{$this->eventRequest->id}): {$e->getMessage()}"
            );
    }
}
