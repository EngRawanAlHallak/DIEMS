<?php

namespace App\Actions\Admin\RequestsScreen;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\CompanyRequest;
use App\Jobs\Company\UploadCompanyDocuments;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitCompanyAmendmentAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(int $requestId, array $data, array $files = []): CompanyRequest
    {
        return $this->executeAction(
            function () use ($requestId, $data, $files) {
                $companyRequest = CompanyRequest::lockForUpdate()->findOrFail($requestId);

                if ($companyRequest->request_status !== 'action_required') {
                    throw ValidationException::withMessages([
                        'status' => ['هذا الطلب غير متاح للتعديل حالياً.'],
                    ]);
                }

                // ترجمة الحقول النصية المتوفرة
                if (isset($data['company_name'])) {
                    $data['company_name'] = $this->translator->execute($data['company_name']);
                }
                if (isset($data['company_description'])) {
                    $data['company_description'] = $this->translator->execute($data['company_description']);
                }
                if (isset($data['nationality'])) {
                    $data['nationality'] = $this->translator->execute($data['nationality']);
                }
                if (isset($data['address'])) {
                    $data['address'] = $this->translator->execute($data['address']);
                }

                // تحديث حالة الطلب وتنظيف الملاحظات الإدارية
                $data['request_status'] = 'pending';
                $data['admin_notes']    = null;

                $companyRequest->update($data);

                // معالجة الملفات المرفوعة
                $tempFiles = [];

                if (!empty($files) && is_array($files)) {
                    foreach ($files as $docData) {
                        if (isset($docData['file']) && $docData['file']->isValid() && isset($docData['type'])) {
                            $file = $docData['file'];
                            $fileType = $docData['type'];

                            // حذف الملف القديم
                            $oldDocument = $companyRequest->documents()->where('file_type', $fileType)->first();

                            if ($oldDocument) {
                                Storage::disk(config('filesystems.default'))->delete($oldDocument->file_path);
                                $oldDocument->delete();
                            }

                            // تخزين مؤقت للملف الجديد
                            $tempPath = $file->store('temp', 'local');

                            $tempFiles[] = [
                                'temp_path'     => $tempPath,
                                'file_type'     => $fileType,
                                'original_name' => $file->getClientOriginalName()
                            ];
                        }
                    }
                }

                if (!empty($tempFiles)) {
                    UploadCompanyDocuments::dispatch($companyRequest, $tempFiles);
                }

                // تنظيف كاش Redis
                Cache::forget("admin:company_request_detail:{$companyRequest->id}");

                return $companyRequest;
            },
            [
                'ar' => "تم إعادة تقديم وتعديل بيانات طلب الشركة رقم (#{$requestId}) بنجاح",
                'en' => "Company request (#{$requestId}) amendment submitted successfully"
            ],
            [
                'request_id'   => $requestId,
                'files_count'  => count($files)
            ],
            true
        );
    }
}
