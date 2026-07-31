<?php

namespace App\Actions\Admin\RequestsScreen;

use App\Actions\General\BaseAction;
use App\Jobs\Company\SendCompanyRequestStatusEmailJob;
use App\Models\CompanyRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class UpdateCompanyRequestStatusAction extends BaseAction
{
    public function execute(int $requestId, string $status, ?string $adminNotes = null): CompanyRequest
    {
        return $this->executeAction(
            function () use ($requestId, $status, $adminNotes) {
                $companyRequest = CompanyRequest::lockForUpdate()->find($requestId);

                $allowedStatuses = ['approved', 'rejected', 'pending', 'action_required'];
                if (!in_array($status, $allowedStatuses)) {
                    throw ValidationException::withMessages([
                        'status' => 'Enter a valid status [approved, rejected, pending, action_required]',
                    ]);}
                $amendmentUrl = null;

                if($companyRequest->request_status == 'approved'){
                    throw ValidationException::withMessages([
                        'notice' => 'This Requist is approved already ....',
                    ]);
                }

                if($status === 'approved' && $companyRequest->request_status == 'pending') {

                    $companyRequest->update(['request_status'   => 'approved']);
                    dispatch(new SendCompanyRequestStatusEmailJob($companyRequest, 'approved'));

                }else if ($status === 'rejected' && $companyRequest->request_status == 'pending') {

                    $companyRequest->update(['request_status' => 'rejected']);
                    dispatch(new SendCompanyRequestStatusEmailJob($companyRequest, 'rejected'));

                }else if ($status === 'pending') {
                    if($companyRequest->payment_status !== 'unpaid') {
                        throw ValidationException::withMessages([
                            'notice' => 'This Requist is paid from the company, cant pending it.....',
                        ]);
                    }
                    $companyRequest->update(['request_status' => 'pending']);
                    dispatch(new SendCompanyRequestStatusEmailJob($companyRequest, 'pending'));

                }else if ($status === 'action_required') {
                    if (empty($adminNotes)) {
                        throw ValidationException::withMessages([
                            'admin_notes' => 'Admin notes are required when requesting an amendment.',
                        ]);
                    }

                    if ($companyRequest->request_status !== 'pending') {
                        throw ValidationException::withMessages([
                            'status' => 'Cant order data update after being the status not pending.',
                        ]);
                    }

                    // توليد رابط خلفية مشفر صالح لمدة 48 ساعة
                    // ملاحظة: نقوم بدمج هذا التوقيع مع رابط الـ React الخاص ببوابة الشركات
                    $backendSignedUrl = URL::temporarySignedRoute(
                        'api.company.request.amendment', // اسم الـ Route الذي سيفحص التوقيع لاحقاً
                        now()->addHours(48),
                        ['request' => $companyRequest->id]
                    );

                    // تحويل الرابط ليوجه للـ Frontend (React) مع إرفاق التوقيع كـ Query String
                    $frontendBase = config('app.frontend_url') . 'companies/register';
                    $amendmentUrl = $frontendBase . '?' . parse_url($backendSignedUrl, PHP_URL_QUERY);


                    $companyRequest->update([
                        'request_status' => $status,
                        'admin_notes'    => $adminNotes
                    ]);

                    dispatch(new SendCompanyRequestStatusEmailJob($companyRequest, $status, $adminNotes, $amendmentUrl));
                }

                return $companyRequest;
            },
            [
                'ar' => "تم تغيير حالة طلب الشركة رقم (#{$requestId}) إلى ({$status}) بنجاح",
                'en' => "Company request (#{$requestId}) status updated to ({$status}) successfully"
            ],
            [
                'request_id'  => $requestId,
                'status'      => $status,
                'admin_notes' => $adminNotes
            ],
            true
        );
    }
}
