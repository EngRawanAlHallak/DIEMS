<?php

namespace App\Actions\Company;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadMediaToS3Action
{
    /**
     * رفع ملف إلى S3 وحذف الملف القديم إذا وُجد
     */
    public function execute(UploadedFile|string $file, string $folder = 'main_pages', ?string $oldPath = null): string
    {
        // حذف الملف القديم من S3 عند التحديث
        if ($oldPath && Storage::disk('s3')->exists($oldPath)) {
            Storage::disk('s3')->delete($oldPath);
        }

        if ($file instanceof UploadedFile) {
            return Storage::disk('s3')->putFile($folder, $file, 'public');
        }

        // في حال تمرير مسار كملف مؤقت من الـ Job
        if (is_string($file) && Storage::disk('local')->exists($file)) {
            $fileName = basename($file);
            $s3Path = $folder . '/' . $fileName;
            Storage::disk('s3')->put($s3Path, Storage::disk('local')->get($file), 'public');
            Storage::disk('local')->delete($file);
            return $s3Path;
        }

        return '';
    }
}
