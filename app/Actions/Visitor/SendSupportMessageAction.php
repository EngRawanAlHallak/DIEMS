<?php

namespace App\Actions\Visitor;

use App\Http\Resources\EventResource;
use App\Jobs\Notification\SendAdminNotificationJob;
use App\Mail\SupportMessageMail;
use App\Models\ExhibitionProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendSupportMessageAction
{
    public function execute(string $email, string $subject, string $message)
    {
        $exhibitionEmail = Cache::rememberForever('exhibition_contact_email', function () {
            return ExhibitionProfile::value('contact_email') ?? config('mail.from.address');
        });

        Mail::to($exhibitionEmail)
            ->send(new SupportMessageMail(
                $email,
                $subject,
                $message
            ));

        $user = User::where('email', $email)->first();
        $isCompany = $user && $user->hasRole('company');

        // 3. تحديد نوع الإشعار والجهة المرسلة
        $notificationType = $isCompany ? 'company_complaint' : 'visitor_complaint';
        $senderType       = $isCompany ? 'company' : 'visitor';
        $senderLabel      = $isCompany ? 'a company' : 'a visitor';

        // 4. إرسال إشعار للأدمن (باللغة الإنجليزية)
        SendAdminNotificationJob::dispatch([
            'title'  => 'New Support Message',
            'body'   => "A new message has been received from {$email} ({$senderLabel}) regarding: {$subject}.",
            'type'   => $notificationType,
            'sender' => $senderType,
            'data'   => [
                'email'   => $email,
                'subject' => $subject
            ]
        ]);
    }
}
