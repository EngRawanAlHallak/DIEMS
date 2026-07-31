<?php

namespace App\Actions\Admin\RequestsScreen;

use App\Actions\General\BaseAction;
use App\Http\Resources\AdminCompanyRequestDetailResource;
use App\Models\CompanyRequest;
use Illuminate\Validation\ValidationException;

class VerifyCompanyAmendmentAction extends BaseAction
{
    public function execute(int $requestId)//: CompanyRequest
    {
        $companyRequest = CompanyRequest::with('documents')->findOrFail($requestId);

        if ($companyRequest->request_status !== 'action_required') {
            throw ValidationException::withMessages([
                'status' => ['هذا الطلب غير مفتوح للتعديلات حالياً.'],
            ]);
        }

        //return $companyRequest;
        return AdminCompanyRequestDetailResource::make($companyRequest)->resolve();

    }
}
