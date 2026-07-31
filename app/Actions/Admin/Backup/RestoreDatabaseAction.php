<?php

namespace App\Actions\Admin\Backup;

use App\Actions\General\BaseAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class RestoreDatabaseAction extends BaseAction
{
    public function __construct(
        protected BackupDatabaseAction $backupAction
    ) {}

    public function execute(string $fileName): bool
    {
        // 1. زيادة مهلة التنفيذ لأن العملية تتضمن (نسخ احتياطي + تنزيل + استعادة)
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        // 2. أخذ نسخة أمان احتياطية من البيانات الحالية قبل أي تعديل
        $this->backupAction->execute();

        // 3. التحقق من وجود ملف الاستعادة على S3
        $s3Path = "backups/{$fileName}";
        $disk   = Storage::disk('s3');

        if (! $disk->exists($s3Path)) {
            throw new Exception("ملف النسخة الاحتياطية المطلوبة غير موجود على S3.");
        }

        $tempPath = storage_path("app/temp/restore-{$fileName}");

        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // 4. تنزيل الملف المراد استادته مؤقتاً
        file_put_contents($tempPath, $disk->get($s3Path));

        // 5. إغلاق اتصال قاعدة البيانات لمنع أقفال الـ Deadlock أثناء psql
        DB::disconnect();

        // 6. قراءة إعدادات الاتصال
        $dbConnection = config('database.default');
        $dbHost       = config("database.connections.{$dbConnection}.host");
        $dbPort       = config("database.connections.{$dbConnection}.port");
        $dbName       = config("database.connections.{$dbConnection}.database");
        $dbUser       = config("database.connections.{$dbConnection}.username");
        $dbPass       = config("database.connections.{$dbConnection}.password");

        // 1. مسح البيانات الحالية وإعادة تنظيف الـ Schema قبل الاستعادة
        DB::statement('DROP SCHEMA public CASCADE;');
        DB::statement('CREATE SCHEMA public;');
        DB::statement('GRANT ALL ON SCHEMA public TO public;');

// 2. بعد التنظيف التام، نفّذي أمر psql للاستعادة
        $command = "PGPASSWORD=\"{$dbPass}\" psql -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbName} < \"{$tempPath}\" 2>&1";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            // طباعة الخطأ الفعلي الصادر من psql في الاستثناء لتعرفي السبب بدقة
            $errorDetails = implode("\n", $output);
            throw new Exception("فشلت عملية استعادة قاعدة البيانات: " . $errorDetails);
        }

        // 8. تنظيف الملف المؤقت
        @unlink($tempPath);

        // 9. إعادة الاتصال بقاعدة البيانات
        DB::reconnect();

        if ($returnVar !== 0) {
            throw new Exception("فشلت عملية استعادة قاعدة البيانات. رمز الخطأ: {$returnVar}");
        }

        // 💡 10. خطوة حاسمة: تصحيح الـ Sequences لكل جداول PostgreSQL تلقائياً لتفادي Duplicate Keys
        $this->resetPostgresSequences();

        // 11. تسجيل الـ Activity Log للعملية بنجاح
        $this->recordActivity(
            [
                'ar' => "تم أخذ نسخة أمان احتياطية واستعادة قاعدة البيانات بنجاح من: {$fileName}",
                'en' => "Safety backup created and database restored successfully from: {$fileName}",
            ],
            null,
            [
                'file_name'  => $fileName,
                'event_type' => 'updated',
            ]
        );

        return true;
    }

    /**
     * إعادة ضبط الـ Sequences في PostgreSQL لجميع الجداول التي تحتوي على Primary Key Auto-Increment
     */
    /**
     * إعادة ضبط الـ Sequences في PostgreSQL لجميع الجداول التي تحتوي على Primary Key Auto-Increment
     */
    private function resetPostgresSequences(): void
    {
        $sql = "
            DO $$
            DECLARE
                r RECORD;
            BEGIN
                FOR r IN (
                    SELECT table_name, column_name, sequencename AS sequence_name
                    FROM information_schema.columns
                    JOIN pg_sequences ON sequencename = table_name || '_' || column_name || '_seq'
                )
                LOOP
                    EXECUTE format('SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %I), 1))', r.sequence_name, r.column_name, r.table_name);
                END LOOP;
            END $$;
        ";

        DB::statement($sql);
    }
}
