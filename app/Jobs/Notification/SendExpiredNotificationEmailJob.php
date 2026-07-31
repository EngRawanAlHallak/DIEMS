<?php

namespace App\Jobs\Notification;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ExhibitionProfile;

class SendExpiredNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $recipientEmail,
        public string $recipientName,
        public string $type // 'company' or 'event'
    ) {}

    public function handle(): void
    {
        try {
            $exhibitionEmail = Cache::rememberForever('exhibition_contact_email', function () {
                return ExhibitionProfile::value('contact_email') ?? config('mail.from.address');
            });
            $exhibitionName = 'Damascus International Fair';

            config([
                'mail.from.address' => $exhibitionEmail,
                'mail.from.name'    => $exhibitionName
            ]);

            $typeText = $this->type === 'company' ? 'Exhibition Application' : 'Event Organizing Application';

            $header = "
            <div style='background-color: #f8fafc; padding: 40px 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                    <div style='background-color: #1a365d; padding: 30px; text-align: center; border-bottom: 4px solid #c5a059;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase;'>Damascus International Fair</h1>
                        <p style='color: #cbd5e1; margin: 5px 0 0 0; font-size: 14px;'>Official Exhibition Management</p>
                    </div>
                    <div style='padding: 40px 30px; color: #334155; line-height: 1.7; font-size: 15px;'>
            ";

            $footer = "
                    </div>
                    <div style='background-color: #f1f5f9; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0;'>
                        <p style='color: #64748b; font-size: 13px; margin: 0;'>
                            This is an automated official communication.<br>
                            &copy; " . date('Y') . " Damascus International Fair (DIEMS). All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
            ";

            $body = "
                <h2 style='color: #991b1b; margin-top: 0;'>Application Expired</h2>
                <p>Dear <strong>{$this->recipientName}</strong>,</p>
                <p>This is an official notification regarding your <strong>{$typeText}</strong>.</p>
                <p>We regret to inform you that the 72-hour payment window for your approved application has <strong>expired</strong>.</p>

                <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 25px 0; color: #991b1b; font-size: 14px;'>
                    <strong>Status Update:</strong> Due to the lack of payment within the stipulated timeframe, your reservation has been automatically canceled, and the allocated space/slot has been released back into the system.
                </div>

                <p>If you wish to participate in the fair, you will need to submit a new application through our official portal.</p>
                <p>For any inquiries, please contact our support team.</p>
                <p>Best regards,<br><strong>DIEMS Exhibition Management Team</strong></p>
            ";

            $htmlContent = $header . $body . $footer;

            Mail::send([], [], function (Message $message) use ($htmlContent) {
                $message->to($this->recipientEmail)
                    ->subject('Notice: Your Application Has Expired - Damascus International Fair')
                    ->html($htmlContent);
            });

        } catch (\Exception $e) {
            Log::error("[Expired Email Job Failed] Email: {$this->recipientEmail}, Error: " . $e->getMessage());
        }
    }
}
