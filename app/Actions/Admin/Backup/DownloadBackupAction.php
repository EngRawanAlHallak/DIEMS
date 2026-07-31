<?php

namespace App\Actions\Admin\Backup;

use App\Actions\General\BaseAction;
use Illuminate\Support\Facades\Storage;
use Exception;

class DownloadBackupAction extends BaseAction
{
    public function execute(string $fileName): string
    {
        return $this->executeAction(
            function () use ($fileName) {
                $s3Path = "backups/{$fileName}";
                $disk   = Storage::disk('s3');

                if (! $disk->exists($s3Path)) {
                    throw new Exception("ملف النسخة الاحتياطية غير موجود على S3.");
                }

                return $disk->temporaryUrl(
                    $s3Path,
                    now()->addMinutes(15),
                    [
                        'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"',
                    ]
                );
            },
            [
                'ar' => "تم توليد رابط تحميل النسخة الاحتياطية: {$fileName}",
                'en' => "Backup download link generated for: {$fileName}",
            ],
            [
                'file_name'  => $fileName,
                'event_type' => 'notified',
            ],
            true
        );
    }
}
