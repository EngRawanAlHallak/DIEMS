<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        try {
            $response = Http::timeout(10)->asForm()->post('https://www.cloud.smschef.com/api/send/sms', [
                'secret' => env('SMSCHEF_SECRET'),
                "mode"   => "devices",
                'device' => env('DEVICE_ID'),
                'phone'  => $phone,
                'message' => $message,
                "sim"    => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('[SMS] Failed to send', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                // تسجيل خطأ النظام الخارجي في activity_log
                activity('system_error')
                    ->withProperties([
                        'service'     => self::class,
                        'phone'       => $phone,
                        'http_status' => $response->status(),
                        'response'    => $response->body(),
                    ])
                    ->log("فشل إرسال رسالة SMS إلى الرقم ({$phone}) عبر بوابة SMSChef");

                return false;
            }

            return true;

        } catch (Throwable $e) {
            Log::error('[SMS] Exception occurred during SMS delivery', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            activity('system_error')
                ->withProperties([
                    'service' => self::class,
                    'phone'   => $phone,
                    'error'   => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ])
                ->log("استثناء غير متوقع أثناء إرسال SMS للرقم ({$phone}): {$e->getMessage()}");

            return false;
        }
    }
}
