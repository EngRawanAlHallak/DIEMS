<?php

namespace App\Http\Controllers;

use App\Actions\Visitor\GetCompaniesSearchBar;
use App\Actions\Visitor\GetCompanyDetailAction;
use App\Actions\Visitor\GetProfileAction;
use App\Actions\Visitor\GetTransportationAction;
use App\Actions\Visitor\GetWelcomePageAction;
use App\Actions\Visitor\SendSupportMessageAction;
use App\Http\Resources\CompanyDetailResource;
use App\Http\Resources\VisitorHomePage\HomePageResource;
use App\Models\Company;
use App\Services\HomePageService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    use ApiResponse;
    public function welcomePage(GetWelcomePageAction $action): JsonResponse
    {
        $data = $action->execute();
        return $this->success($data, 'Welcome page content retrieved.');

    }

    public function homePage(Request $request, HomePageService $service): JsonResponse
    {
        // 1. جلب رقم الصفحة من الـ Request (الافتراضي 1)
        $page = $request->query('page', 1);

        // 2. تمرير رقم الصفحة للـ Service
        $data = $service->getHomePageData($page);

        // 3. إرجاع الـ Resource (والذي سيتعامل مع الـ Pagination بداخله كما شرحنا سابقاً)
        return $this->success(
            new HomePageResource($data),
            'Home page data fetched successfully'
        );
    }

    public function getProfile(GetProfileAction $action): JsonResponse
    {
        $data = $action->execute();
        return $this->success($data, 'profile content retrieved.');

    }

    // get companies by sector id or search by name
    public function getCompanies(Request $request, GetCompaniesSearchBar $action): JsonResponse
    {
        $validated = $request->validate([
            'sector_id' => ['sometimes', 'nullable', 'integer', 'exists:sectors,id'],
            'search'    => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $companies = $action->execute(
            sectorId: $validated['sector_id'] ?? null,
            search:   $validated['search']    ?? null,
        );

        return $this->success($companies,
            'Companies fetched successfully'
        );
    }

    public function companyDetails(Company $company, GetCompanyDetailAction $action): JsonResponse
    {
        try {

            $company = $action->execute($company);
            return $this->success(new CompanyDetailResource($company),'Company detail fetched successfully');

        }catch (Exception $exception){
            return $this->error($exception->getMessage());
        }
    }

    public function SupportMessage(Request $request , SendSupportMessageAction $action): JsonResponse
    {
        $validated = $request->validate([
            'email'   => ['required','email'],
            'subject' => ['required','string','max:150'],
            'message' => ['required','string','min:10'],
        ]);

        try {
            $action->execute($validated['email'], $validated['subject'], $validated['message']);

            return $this->success(null, 'Your message has been successfully received. The support team will get back to you soon.');
        }catch (Exception $exception){
            return $this->error($exception->getMessage());
        }
    }

    public function getTranportation(GetTransportationAction $action)
    {
        try{
            $transportations = $action->execute();
            return $this->success($transportations, 'transportation fetched successfully');
        }catch (Exception $exception){
            return $this->error($exception->getMessage());
        }
    }
}
