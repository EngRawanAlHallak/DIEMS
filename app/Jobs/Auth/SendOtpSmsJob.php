<?php

namespace App\Jobs\Auth;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly string $phone,
        private readonly string $otp,
        private readonly int    $ttlMinutes,
    ) {}

    public function handle(SmsService $smsService): void
    {
        $message = "Your DIEMS reset code: {$this->otp}. Valid for {$this->ttlMinutes} minutes.";

        $sent = $smsService->send($this->phone, $message);

        if (!$sent) {
            throw new \Exception("Failed to send SMS to {$this->phone}");
        }

        Log::info('[OTP] SMS sent', ['phone' => $this->phone]);
    }

    public function failed(\Throwable $e): void
    {
        // 1. الكتابة في ملفات Log النظام
        Log::error('[OTP] SMS job failed', [
            'phone' => $this->phone,
            'error' => $e->getMessage(),
        ]);

        // 2. التسجيل في جدول activity_log للأدمن
        activity('system_error')
            ->withProperties([
                'job'       => self::class,
                'phone'     => $this->phone,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'failed_at' => now()->toDateTimeString(),
            ])
            ->log("فشل إرسال الرسالة النصية OTP للرقم ({$this->phone}): {$e->getMessage()}");
    }
}
