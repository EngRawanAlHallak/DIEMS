<?php

namespace App\Actions\Company;

use App\Actions\General\TranslateTextAction;
use App\Jobs\Notification\SendAdminNotificationJob;
use App\Models\EventRequest;
use App\Models\EventSlot;
use App\Models\PricingTier;
use App\Jobs\Company\UploadEventBannerJob;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreEventRequestAction
{
    protected $translator;

    public function __construct(TranslateTextAction $translator)
    {
        $this->translator = $translator;
    }

    public function execute(array $data, ?object $imageFile = null): EventRequest
    {
        // داخل StoreEventRequestAction.php

        return DB::transaction(function () use ($data, $imageFile) {

            $slot = EventSlot::lockForUpdate()->findOrFail($data['slot_id']);

            if (!$slot->available) {
                throw new \Exception("عذراً، هذا التاريخ والوقت محجوزان بالفعل.");
            }
            $translatedTitle = $this->translator->execute($data['event_title']);
            $translatedDescription = $this->translator->execute($data['event_description']);

            $tempPath = null;
            if ($imageFile && $imageFile->isValid()) {
                $tempPath = $imageFile->store('temp', 'local');
            }
            $eventRequest = EventRequest::create([
                'slot_id' => $slot->id,
                'sector_id' => $data['sector_id'],
                'hall_id' => null, // لا توجد قاعة حالياً
                'organizer_name' => $data['organizer_name'],
                'organizer_email' => $data['organizer_email'],
                'organizer_phone' => $data['organizer_phone'],
                'event_title' => $translatedTitle,
                'event_description' => $translatedDescription,
                'Expected_attendance' => $data['Expected_attendance'],
                'equipment_needed' => $data['equipment_needed'] ?? null,
                'image' => null,
                'is_special' => $data['is_special'] ?? false,
                'request_status' => 'pending',
                'payment_status' => 'unpaid',
                'total_price' => null, // يُحسب لاحقاً عند القبول
                'payment_due_date' => null, // تبدأ المهلة بعد موافقة الإدارة
            ]);

            if ($tempPath) {
                UploadEventBannerJob::dispatch($eventRequest, $tempPath);
            }

            $eventTitleEn = $data['event_title']['en'] ?? ($data['event_title']['ar'] ?? 'A new event');
            SendAdminNotificationJob::dispatch([
                'title'  => 'New Event Request',
                'body'   => "An event request titled '{$eventTitleEn}' has been submitted by {$data['organizer_name']}.",
                'type'   => 'event_request',
                'sender' => 'company',
            ]);

            return $eventRequest;
        });
    }
}
