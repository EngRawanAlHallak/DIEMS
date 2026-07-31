<?php

namespace App\Jobs\Event;

use App\Models\EventRequest;
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

class SendEventStatusEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 45;

    public EventRequest $eventRequest;
    public string $statusType;

    public function __construct(EventRequest $eventRequest, string $statusType)
    {
        $this->eventRequest = $eventRequest;
        $this->statusType = $statusType;
    }

    public function handle(): void
    {
        $exhibitionEmail = Cache::rememberForever('exhibition_contact_email', function () {
            return ExhibitionProfile::value('contact_email') ?? config('mail.from.address');
        });
        $exhibitionName = 'Damascus International Fair';

        config([
            'mail.from.address' => $exhibitionEmail,
            'mail.from.name'    => $exhibitionName
        ]);

        $emailDetails = $this->getEmailContentByStatus();
        $recipientEmail = $this->eventRequest->organizer_email;

        if (!$recipientEmail) {
            Log::error("[Event Mail] Recipient email not found for Request ID: {$this->eventRequest->id}");
            return;
        }

        Mail::send([], [], function (Message $message) use ($recipientEmail, $emailDetails, $exhibitionEmail, $exhibitionName) {
            $message
                ->to($recipientEmail)
                ->from($exhibitionEmail, $exhibitionName)
                ->subject($emailDetails['subject'])
                ->html($emailDetails['html']);
        });

        Log::info("[Event Mail] Email sent successfully", [
            'request_id'  => $this->eventRequest->id,
            'status_type' => $this->statusType,
            'to_email'    => $recipientEmail
        ]);
    }

    private function getEmailContentByStatus(): array
    {
        $subject = '';
        $body = '';
        $organizerName = htmlspecialchars($this->eventRequest->organizer_name ?? 'Event Organizer');

        // --- Header & Footer Templates ---
        $header = "
        <div style='background-color: #f8fafc; padding: 40px 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                <div style='background-color: #1a365d; padding: 30px; text-align: center; border-bottom: 4px solid #c5a059;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase;'>Damascus International Fair</h1>
                    <p style='color: #cbd5e1; margin: 5px 0 0 0; font-size: 14px;'>Event Management & Coordination</p>
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
                $totalPrice = number_format($this->eventRequest->total_price, 2);
                $deposit = number_format($this->eventRequest->required_deposit, 2);
                $dueDate = $this->eventRequest->payment_due_date ? Carbon::parse($this->eventRequest->payment_due_date)->format('F j, Y, g:i a') : 'N/A';

                $paymentUrl = URL::temporarySignedRoute(
                    'event.payments.pay-direct',
                    now()->addHours(72),
                    ['id' => $this->eventRequest->id]
                );

                $subject = 'Action Required: Event Slot Booking Confirmed';
                $body = "
                    <h2 style='color: #1a365d; margin-top: 0;'>Event Request Approved</h2>
                    <p>Dear <strong>{$organizerName}</strong>,</p>
                    <p>We are pleased to inform you that your request to host an event at the Damascus International Fair has been <strong>officially approved</strong>, and your requested time slot has been tentatively secured.</p>

                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 25px 0;'>
                        <h3 style='color: #1a365d; margin-top: 0; border-bottom: 2px solid #c5a059; padding-bottom: 10px; display: inline-block;'>Booking & Financial Summary</h3>
                        <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                            <tr>
                                <td style='padding: 8px 0; color: #64748b;'>Total Event Fee:</td>
                                <td style='padding: 8px 0; font-weight: bold; text-align: right; color: #0f172a;'>{$totalPrice} SAR</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0 0 0; color: #dc2626; font-weight: bold;'>Payment Due Date:</td>
                                <td style='padding: 12px 0 0 0; font-weight: bold; text-align: right; color: #dc2626;'>{$dueDate}</td>
                            </tr>
                        </table>
                    </div>

                    <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin-bottom: 25px; color: #991b1b; font-size: 14px;'>
                        <strong>Urgent Notice:</strong> To finalize and confirm your booking, the deposit must be settled within <strong>72 Hours</strong>. If payment is not received by the due date, the system will automatically release the slot to other applicants.
                    </div>

                    <div style='text-align: center; margin: 35px 0;'>
                        <a href='{$paymentUrl}' style='background-color: #c5a059; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;'>Proceed to Secure Payment</a>
                    </div>
                ";
                break;

            case 'rejected':
            case 'auto_rejected':
                $subject = 'Update on Your Event Request';
                $body = "
                    <h2 style='color: #1a365d; margin-top: 0;'>Event Status Update</h2>
                    <p>Dear <strong>{$organizerName}</strong>,</p>
                    <p>Thank you for considering the Damascus International Fair as the venue for your event.</p>
                    <p>We regret to inform you that we cannot approve your request for the specified time slot. This is typically due to scheduling conflicts, or the slot has already been secured by a prior applicant.</p>
                    <p>You are highly encouraged to browse our available schedule and submit a new request for an alternative date or time.</p>
                    <p>Sincerely,</p>
                    <p><strong>Event Coordination Team</strong></p>
                ";
                break;

            case 'cancelled':
                $subject = 'Notice: Event Reservation Cancelled';
                $body = "
                    <h2 style='color: #475569; margin-top: 0;'>Reservation Cancelled</h2>
                    <p>Dear <strong>{$organizerName}</strong>,</p>
                    <p>This email serves as an official confirmation that your event reservation has been successfully <strong>cancelled</strong>.</p>
                    <p>The time slot associated with your request has been released back into the available pool.</p>
                    <p>If you believe this cancellation was made in error, please contact our support desk immediately.</p>
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
        Log::error('[Event Mail] Job processing failed completely', [
            'request_id'  => $this->eventRequest->id,
            'status_type' => $this->statusType,
            'error'       => $e->getMessage(),
        ]);
    }
}
