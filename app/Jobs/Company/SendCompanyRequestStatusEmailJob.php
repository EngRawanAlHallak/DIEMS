<?php

namespace App\Jobs\Company;

use App\Models\CompanyRequest;
use App\Models\ExhibitionProfile;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\URL;

class SendCompanyRequestStatusEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 45;

    public CompanyRequest $companyRequest;
    public string $statusType;
    public ?string $adminNotes;
    public ?string $amendmentUrl;

    public function __construct(CompanyRequest $companyRequest, string $statusType, ?string $adminNotes = null, ?string $amendmentUrl = null)
    {
        $this->companyRequest = $companyRequest;
        $this->statusType = $statusType;
        $this->adminNotes = $adminNotes;
        $this->amendmentUrl = $amendmentUrl;
    }

    public function handle(): void
    {
        $exhibitionEmail = Cache::rememberForever('exhibition_contact_email', function () {
            return ExhibitionProfile::value('contact_email') ?? config('mail.from.address');
        });
        $exhibitionName = 'Damascus International Fair';
        Log::info("Sending company email from: {$exhibitionEmail}");

        config([
            'mail.from.address' => $exhibitionEmail,
            'mail.from.name'    => $exhibitionName
        ]);

        $emailDetails = $this->getEmailContentByStatus();
        $recipientEmail = $this->companyRequest->email;

        if (!$recipientEmail) {
            Log::error("[Company Mail] Recipient email not found for Request ID: {$this->companyRequest->id}");
            return;
        }

        Mail::send([], [], function (Message $message) use ($recipientEmail, $emailDetails, $exhibitionEmail, $exhibitionName) {
            $message
                ->to($recipientEmail)
                ->from($exhibitionEmail, $exhibitionName)
                ->subject($emailDetails['subject'])
                ->html($emailDetails['html']);
        });

        Log::info("[Company Mail] Email sent successfully", [
            'request_id'  => $this->companyRequest->id,
            'status_type' => $this->statusType,
            'to_email'    => $recipientEmail
        ]);
    }

    private function getEmailContentByStatus(): array
    {
        $subject = '';
        $body = '';
        $companyName = $this->companyRequest->getTranslation('company_name', 'en', false) ?? 'Valued Partner';

        // --- Header & Footer Templates ---
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

        switch ($this->statusType) {
            case 'approved':
                $totalPrice = number_format($this->companyRequest->total_price, 2);
                $deposit = number_format($this->companyRequest->required_deposit, 2);
                $dueDate = $this->companyRequest->payment_due_date ? Carbon::parse($this->companyRequest->payment_due_date)->format('F j, Y, g:i a') : 'N/A';

                $paymentUrl = URL::temporarySignedRoute(
                    'company.payments.pay-direct',
                    now()->addHours(72),
                    ['id' => $this->companyRequest->id]
                );

                $subject = 'Action Required: Your Exhibition Application is Approved';
                $body = "
                    <h2 style='color: #1a365d; margin-top: 0;'>Application Approved</h2>
                    <p>Dear <strong>{$companyName}</strong>,</p>
                    <p>We are pleased to officially inform you that your application to participate in the upcoming Damascus International Fair has been reviewed and <strong>approved</strong>.</p>

                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 25px 0;'>
                        <h3 style='color: #1a365d; margin-top: 0; border-bottom: 2px solid #c5a059; padding-bottom: 10px; display: inline-block;'>Financial Summary</h3>
                        <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                            <tr>
                                <td style='padding: 8px 0; color: #64748b;'>Total Participation Fee:</td>
                                <td style='padding: 8px 0; font-weight: bold; text-align: right; color: #0f172a;'>{$totalPrice} SAR</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #e2e8f0;'>Required Deposit (25%):</td>
                                <td style='padding: 8px 0; font-weight: bold; text-align: right; color: #c5a059; border-bottom: 1px solid #e2e8f0;'>{$deposit} SAR</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0 0 0; color: #dc2626; font-weight: bold;'>Payment Due Date:</td>
                                <td style='padding: 12px 0 0 0; font-weight: bold; text-align: right; color: #dc2626;'>{$dueDate}</td>
                            </tr>
                        </table>
                    </div>

                    <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin-bottom: 25px; color: #991b1b; font-size: 14px;'>
                        <strong>Important:</strong> This payment link is exclusively valid for <strong>72 Hours</strong>. Failure to secure the deposit before the due date will result in the automatic cancellation of your reservation and the release of your allocated space.
                    </div>

                    <div style='text-align: center; margin: 35px 0;'>
                        <a href='{$paymentUrl}' style='background-color: #c5a059; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;'>Proceed to Secure Payment</a>
                    </div>
                    <p>We look forward to welcoming you to the fair.</p>
                ";
                break;

            case 'rejected':
                $subject = 'Update on Your Exhibition Application';
                $body = "
                    <h2 style='color: #1a365d; margin-top: 0;'>Application Status Update</h2>
                    <p>Dear <strong>{$companyName}</strong>,</p>
                    <p>Thank you for your interest in participating in the Damascus International Fair.</p>
                    <p>After a comprehensive review by our organizing committee, we regret to inform you that we are unable to accommodate your application at this time. This decision is typically based on current sector capacity limits or specific exhibition criteria for this edition.</p>
                    <p>We highly value your business and encourage you to apply for our future events.</p>
                    <p>Sincerely,</p>
                    <p><strong>The Organizing Committee</strong></p>
                ";
                break;

            case 'pending':
                $subject = 'Confirmation: Application Received';
                $body = "
                    <h2 style='color: #1a365d; margin-top: 0;'>Application Successfully Received</h2>
                    <p>Dear <strong>{$companyName}</strong>,</p>
                    <p>This is an official confirmation that your exhibition application has been successfully logged into our system and is currently marked as <strong>Pending Review</strong>.</p>
                    <p>Our administrative team will carefully evaluate your submitted documents and details. You will receive a follow-up email once a formal decision has been made.</p>
                    <p>Thank you for your patience.</p>
                ";
                break;

            case 'action_required':
                Log::info("[url is] {$this->amendmentUrl}}");
                $subject = 'Action Required: Application Amendment Request';
                $notes = htmlspecialchars($this->adminNotes ?? 'Please review and update your application details.');
                $body = "
                    <h2 style='color: #1a365d; margin-top: 0;'>Application Amendment Required</h2>
                    <p>Dear <strong>{$companyName}</strong>,</p>
                    <p>Your application is currently under review. However, our committee requires additional information or modifications to proceed with the final approval.</p>

                    <div style='background-color: #fefce8; border-left: 4px solid #c5a059; padding: 15px; margin: 20px 0;'>
                        <h4 style='margin: 0 0 8px 0; color: #854d0e;'>Official Notes from the Committee:</h4>
                        <p style='margin: 0; color: #713f12; font-style: italic;'>\"{$notes}\"</p>
                    </div>

                    <p>Please utilize the secure link below to update your application. For security purposes, this link will expire in <strong>48 hours</strong>.</p>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$this->amendmentUrl}' style='background-color: #1a365d; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>Update Application</a>
                    </div>
                ";
                break;
        }

        return [
            'subject' => $subject,
            'html'    => $header . $body . $footer
        ];
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Company Mail] Job processing failed completely', [
            'request_id'  => $this->companyRequest->id,
            'status_type' => $this->statusType,
            'error'       => $e->getMessage(),
        ]);
    }
}
