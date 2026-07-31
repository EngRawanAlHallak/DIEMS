<?php

namespace App\Jobs\Company;

use App\Models\EventRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadEventBannerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventRequest;
    protected $tempPath;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(EventRequest $eventRequest, string $tempPath)
    {
        $this->eventRequest = $eventRequest;
        $this->tempPath     = $tempPath;
    }

    public function handle(): void
    {
        if (!Storage::disk('local')->exists($this->tempPath)) {
            Log::warning("Temporary event image not found for request ID: {$this->eventRequest->id}");
            return;
        }

        try {
            $fileContents = Storage::disk('local')->get($this->tempPath);
            $fileName     = basename($this->tempPath) . '.jpg';
            $s3Path       = "events/banners/{$this->eventRequest->id}_{$fileName}";

            Storage::disk('s3')->put($s3Path, $fileContents, 'public');

            $this->eventRequest->update([
                'image' => Storage::disk('s3')->url($s3Path)
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to upload event banner to S3 (Request ID: {$this->eventRequest->id}): " . $e->getMessage());
            throw $e;
        } finally {
            Storage::disk('local')->delete($this->tempPath);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[UploadEventBannerJob] Job failed completely', [
            'event_request_id' => $this->eventRequest->id,
            'error'            => $e->getMessage(),
        ]);

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
            ->log("فشل رفع بنر الفعالية الخاصة بطلب الفعالية رقم (#{$this->eventRequest->id}): {$e->getMessage()}");
    }
}
