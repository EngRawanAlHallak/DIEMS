<?php

namespace App\Jobs\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SendAdminNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3; // التكرار 3 مرات عند الفشل
    public int $backoff = 7; // الانتظار 7 ثواني قبل المحاولة التالية
    public int $timeout = 45;

    protected array $notificationData;

    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }

    public function handle(): void
    {
        // 1. جلب التوكن الخاص بالأدمن
        $admin = User::role('admin')->first();
        $fcmToken = $admin?->fcm_token;

        if (!$fcmToken) {
            Log::warning('Firebase Notification Aborted: Admin FCM token not found.');
            return;
        }

        // 2. إرسال الإشعار عبر Firebase (باستخدام HTTP Client)
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
        $accessToken     = $this->getFirebaseAccessToken($credentialsPath);

        $response = Http::withToken($accessToken)
            ->post(env('FIREBASE_URL'), [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $this->notificationData['title'],
                        'body'  => $this->notificationData['body'] ?? '',
                    ],
                    'data' => [
                        'type'    => $this->notificationData['type'],
                        'sender'  => $this->notificationData['sender'] ?? '',
                        'payload' => json_encode($this->notificationData['data'] ?? [])
                    ]
                ]
            ]);

        // 3. التحقق من النجاح ثم الحفظ بالداتا بيز
        if ($response->successful()) {
            Notification::create([
                'title'  => $this->notificationData['title'],
                'body'   => $this->notificationData['body'] ?? null,
                'type'   => $this->notificationData['type'],
                'sender' => $this->notificationData['sender'] ?? null,
                'data'   => $this->notificationData['data'] ?? null,
                'status' => 'unread',
            ]);

            // رفع إصدار الكاش لكي تظهر النوتيفيكيشن الجديدة فورا للأدمن
            Cache::increment('admin_notifications_version');
        } else {
            // رمي استثناء لكي يعيد الـ Job المحاولة (Tries)
            throw new \Exception('Firebase FCM Error: ' . $response->body());
        }
    }

    /**
     * دالة مساعدة لاستخراج التوكن من ملف الـ JSON الخاص بـ Firebase
     */
    private function getFirebaseAccessToken(string $credentialsPath): string
    {
        return Cache::remember('firebase_fcm_access_token', 3300, function () use ($credentialsPath) {
            $client = new \Google\Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $token = $client->fetchAccessTokenWithAssertion();

            return $token['access_token'];
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        $admin = User::role('admin')->first();

        // 1. الكتابة في ملفات Log النظام
        Log::error('[SendAdminNotificationJob] Failed to send admin push notification', [
            'notification_type'  => $this->notificationData['type'] ?? 'N/A',
            'notification_title' => $this->notificationData['title'] ?? 'N/A',
            'error'              => $e->getMessage(),
        ]);

        // 2. التسجيل في جدول activity_log للـ Admin
        $activity = activity('system_error');

        if ($admin) {
            $activity->performedOn($admin);
        }

        $activity->withProperties([
            'job'               => self::class,
            'notification_data' => $this->notificationData,
            'admin_id'          => $admin?->id,
            'error'             => $e->getMessage(),
            'trace'             => $e->getTraceAsString(),
            'failed_at'         => now()->toDateTimeString(),
        ])
            ->log("فشل إرسال إشعار Firebase للأدمن بعنوان (" . ($this->notificationData['title'] ?? 'بدون عنوان') . "): {$e->getMessage()}");
    }
}
