<?php

namespace App\Jobs\Company;

use App\Models\CompanyRequest;
use App\Actions\company\PromoteApprovedRequestsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Cache;
use App\Models\ExhibitionProfile;

class PromoteCompanyAndSendCredentialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public CompanyRequest $companyRequest)
    {}

    public function handle(PromoteApprovedRequestsAction $action): void
    {
        Log::info("in PromoteCompanyAndSendCredentialsJob ");
        try {
            $credentials = $action->execute($this->companyRequest);

            // في حال تم تخطي الطلب (مثلاً الإيميل موجود مسبقاً)
            if (empty($credentials)) {
                Log::warning("No credentials returned for Request ID: {$this->companyRequest->id}");
                return;
            }

            $user = $credentials['user'];
            $password = $credentials['password'];

            // رابط لوحة تحكم الشركات في الفرونت إند
            //$loginUrl = config('app.frontend_url') . '/companies/login';
            $loginUrl = 'http://localhost:5175//companies/login';

            // 2. إعداد مرسل الإيميل الرسمي
            $exhibitionEmail = Cache::rememberForever('exhibition_contact_email', function () {
                return ExhibitionProfile::value('contact_email') ?? config('mail.from.address');
            });
            $exhibitionName = 'Damascus International Fair';

            config([
                'mail.from.address' => $exhibitionEmail,
                'mail.from.name'    => $exhibitionName
            ]);

            // 3. بناء قالب الـ HTML الاحترافي
            $header = "
            <div style='background-color: #f8fafc; padding: 40px 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                    <div style='background-color: #1a365d; padding: 30px; text-align: center; border-bottom: 4px solid #c5a059;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase;'>Damascus International Fair</h1>
                        <p style='color: #cbd5e1; margin: 5px 0 0 0; font-size: 14px;'>Official Exhibitor Portal</p>
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
                <h2 style='color: #1a365d; margin-top: 0;'>Welcome Aboard!</h2>
                <p>Dear <strong>{$user->name}</strong>,</p>
                <p>We are pleased to inform you that your payment has been successfully processed. You are now officially registered as an active exhibitor at the Damascus International Fair.</p>
                <p>An official exhibitor account has been provisioned for your company. Please find your secure login credentials below:</p>

                <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 25px 0;'>
                    <h3 style='color: #1a365d; margin-top: 0; border-bottom: 2px solid #c5a059; padding-bottom: 10px; display: inline-block;'>Account Credentials</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                        <tr>
                            <td style='padding: 10px 0; color: #64748b; width: 40%;'>Registered Email:</td>
                            <td style='padding: 10px 0; font-weight: bold; color: #0f172a;'>{$user->email}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; color: #64748b; border-bottom: 1px solid #e2e8f0;'>Temporary Password:</td>
                            <td style='padding: 10px 0; font-weight: bold; color: #c5a059; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 18px; letter-spacing: 1px;'>{$password}</td>
                        </tr>
                    </table>
                </div>

                <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin-bottom: 25px; color: #991b1b; font-size: 14px;'>
                    <strong>Security Notice:</strong> For your security, we highly recommend logging in immediately and changing your temporary password from your account settings.
                </div>

                <div style='text-align: center; margin: 35px 0;'>
                    <a href='{$loginUrl}' style='background-color: #1a365d; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;'>Access Exhibitor Portal</a>
                </div>
                <p>We look forward to a successful exhibition together.</p>
            ";

            $htmlContent = $header . $body . $footer;

            // 4. إرسال الإيميل
            Mail::send([], [], function (Message $message) use ($user, $exhibitionEmail, $exhibitionName, $htmlContent) {
                $message
                    ->to($user->email)
                    ->from($exhibitionEmail, $exhibitionName)
                    ->subject('Official Welcome: Your DIEMS Exhibitor Account Credentials')
                    ->html($htmlContent);
            });

            Log::info("Company promoted and welcome credentials sent successfully for Request ID: {$this->companyRequest->id}");

        } catch (\Exception $e) {
            Log::error("Failed to promote company or send email for Request ID: {$this->companyRequest->id} | Error: " . $e->getMessage());
        }
    }
}
