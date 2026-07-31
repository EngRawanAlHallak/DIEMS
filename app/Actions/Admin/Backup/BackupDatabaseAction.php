<?php

namespace App\Actions\Admin\Backup;

use App\Actions\General\BaseAction;
use Illuminate\Support\Facades\Storage;
use Exception;

class BackupDatabaseAction extends BaseAction
{
    public function execute(): string
    {
        return $this->executeAction(
            function () {
                $fileName = 'db-backup-' . now()->format('Y-m-d_H-i-s') . '.sql';
                $tempPath = storage_path("app/temp/{$fileName}");

                if (! file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }

                $dbConnection = config('database.default');
                $dbHost       = config("database.connections.{$dbConnection}.host");
                $dbPort       = config("database.connections.{$dbConnection}.port");
                $dbName       = config("database.connections.{$dbConnection}.database");
                $dbUser       = config("database.connections.{$dbConnection}.username");
                $dbPass       = config("database.connections.{$dbConnection}.password");

                $command = "PGPASSWORD=\"{$dbPass}\" pg_dump -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbName} -F p > \"{$tempPath}\"";

                exec($command, $output, $returnVar);

                if ($returnVar !== 0 || ! file_exists($tempPath) || filesize($tempPath) === 0) {
                    if (file_exists($tempPath)) {
                        @unlink($tempPath);
                    }
                    throw new Exception("Database dump command failed with exit code {$returnVar}");
                }
                $s3Path      = "backups/" . $fileName;
                $fileContent = file_get_contents($tempPath);

                $uploaded = Storage::disk('s3')->put($s3Path, $fileContent, 'private');
                @unlink($tempPath);

                if (! $uploaded) {
                    throw new Exception("Failed to upload the backup file to AWS S3 bucket.");
                }

                return $s3Path;
            },
            [
                'ar' => 'تم إنشاء نسخة احتياطية لقاعدة البيانات بنجاح ورفعها إلى S3',
                'en' => 'Database backup created and uploaded to S3 successfully',
            ],
            [
                'disk'       => 's3',
                'event_type' => 'created',
            ],
            true
        );
    }
}
