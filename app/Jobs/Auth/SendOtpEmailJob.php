<?php

namespace App\Jobs\Auth;

use App\Models\ExhibitionProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly string $email,
        private readonly string $name,
        private readonly string $otp,
        private readonly int    $ttlMinutes,
    ) {}

    public function handle(): void
    {
        $exhibitionEmail = Cache::rememberForever('exhibition_contact_email', function () {
            return ExhibitionProfile::value('contact_email') ?? config('mail.from.address');
        });
        $exhibitionName = 'DIEMS Helper';

        config([
            'mail.from.address' => $exhibitionEmail,
            'mail.from.name'    => $exhibitionName
        ]);

        Mail::send([], [], function (Message $message) use ($exhibitionEmail, $exhibitionName) {
            $message
                ->to($this->email)
                ->from($exhibitionEmail, $exhibitionName)
                ->subject('Your DIEMS Password Reset Code')
                ->html(
                    "<p>Hello {$this->name},</p>" .
                    "<p>Your password reset code is:</p>" .
                    "<h2 style='letter-spacing:8px;font-size:36px;font-family:monospace;color:#333'>" .
                    "{$this->otp}</h2>" .
                    "<p>This code expires in <strong>{$this->ttlMinutes} minutes</strong>.</p>" .
                    "<p>If you did not request this, please ignore this email.</p>"
                );
        });

        Log::info('[OTP] Email sent', ['email' => $this->email]);
    }

    public function failed(\Throwable $e): void
    {
        // 1. الكتابة في ملفات Log النظام
        Log::error('[OTP] Email job failed', [
            'email' => $this->email,
            'error' => $e->getMessage(),
        ]);

        // 2. التسجيل في جدول activity_log للأدمن
        activity('system_error')
            ->withProperties([
                'job'         => self::class,
                'email'       => $this->email,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'failed_at'   => now()->toDateTimeString(),
            ])
            ->log("فشل إرسال رمز OTP عبر البريد الإلكتروني للعنوان ({$this->email}): {$e->getMessage()}");
    }
}
