<?php

namespace App\Actions\Company;

use App\Actions\General\TranslateTextAction;
use App\Jobs\Company\UploadEventBannerJob;
use App\Jobs\Notification\SendAdminNotificationJob;
use App\Models\EventRequest;
use App\Models\EventSlot;
use Illuminate\Support\Facades\DB;

class StoreEventRequestAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data, ?object $imageFile = null): EventRequest
    {
        return DB::transaction(function () use ($data, $imageFile) {

            $slot = EventSlot::lockForUpdate()
                ->findOrFail($data['slot_id']);

            if (!$slot->available) {
                throw new \Exception(
                    "عذراً، هذا التاريخ والوقت محجوزان بالفعل."
                );
            }

            $translatedTitle = $this->translator->execute(
                $data['event_title']
            );

            $translatedDescription = $this->translator->execute(
                $data['event_description']
            );

            /*
             * نخزن الصورة مؤقتاً على local.
             * لا نخزن أي URL في قاعدة البيانات هنا.
             */
            $tempPath = null;

            if ($imageFile && $imageFile->isValid()) {
                $tempPath = $imageFile->store('temp', 'local');
            }

            $eventRequest = EventRequest::create([
                'slot_id'            => $slot->id,
                'sector_id'          => $data['sector_id'],
                'hall_id'            => null,

                'organizer_name'     => $data['organizer_name'],
                'organizer_email'    => $data['organizer_email'],
                'organizer_phone'    => $data['organizer_phone'],

                'event_title'        => $translatedTitle,
                'event_description'  => $translatedDescription,

                'Expected_attendance' => $data['Expected_attendance'],
                'equipment_needed'    => $data['equipment_needed'] ?? null,

                // مهم: بالبداية NULL
                'image'              => null,

                'is_special'         => $data['is_special'] ?? false,

                'request_status'     => 'pending',
                'payment_status'     => 'unpaid',

                'total_price'        => null,
                'payment_due_date'  => null,
            ]);

            /*
             * الـ Job هو المسؤول عن رفع الصورة إلى S3
             * وتخزين PATH فقط في DB.
             */
            if ($tempPath) {
                UploadEventBannerJob::dispatch(
                    $eventRequest,
                    $tempPath
                );
            }

            $eventTitleEn =
                $data['event_title']['en']
                ?? $data['event_title']['ar']
                ?? 'A new event';

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
