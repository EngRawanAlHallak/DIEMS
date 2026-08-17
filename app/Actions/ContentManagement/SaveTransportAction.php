<?php

namespace App\Actions\ContentManagement;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\Transportation;
use App\Jobs\Company\ProcessMediaUploadJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class SaveTransportAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data, ?Transportation $transport = null): Transportation
    {
        // التحقق ممّا إذا كان السجل موجوداً مسبقاً قبل عملية الحفظ لتحديد الرسالة
        $isUpdate = $transport && $transport->exists;

        return $this->executeAction(
            function () use ($data, $transport) {
                $transport = $transport ?? new Transportation();

                // 1. ترجمة اسم خط المواصلات (إذا كان الحقل مترجم jsonb)
                if (isset($data['name'])) {
                    $data['name'] = is_array($data['name']) ? $data['name'] : $this->translator->execute($data['name']);
                }

                $image = $data['image'] ?? null;
                unset($data['image']);

                // 2. حفظ بيانات الخط
                $transport->fill($data)->save();

                Cache::forget('transportation_page_ar');
                Cache::forget('transportation_page_en');

                // 3. رفع صورة الخط عبر الـ Job
                if ($image instanceof UploadedFile && $image->isValid()) {
                    $tempPath = $image->store('temp', 'local');
                    dispatch(new ProcessMediaUploadJob(
                        $transport,
                        'image',
                        $tempPath,
                        'transports',
                        $transport->image
                    ));
                }

                return $transport;
            },
            [
                'ar' => $isUpdate ? 'تم تحديث بيانات خط المواصلات بنجاح' : 'تم إضافة خط مواصلات جديد بنجاح',
                'en' => $isUpdate ? 'Transport line updated successfully' : 'Transport line created successfully'
            ],
            [],
            true
        );
    }
}
