<?php

namespace App\Actions\Admin\Backup;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ListBackupsAction
{
    public function execute(): array
    {
        $disk = Storage::disk('s3');
        $files = $disk->files('backups');

        $backups = [];

        foreach ($files as $file) {
            // نأخذ الملفات التي تنتهي بـ sql فقط
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $sizeInBytes = $disk->size($file);

                $backups[] = [
                    'file_name'     => basename($file),
                    'file_path'     => $file,
                    'size'          => $this->formatSizeUnits($sizeInBytes),
                    'size_bytes'    => $sizeInBytes,
                    'last_modified' => Carbon::createFromTimestamp($disk->lastModified($file))->toDateTimeString(),
                ];
            }
        }

        usort($backups, fn($a, $b) => strcmp($b['last_modified'], $a['last_modified']));

        return $backups;
    }

    private function formatSizeUnits(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
