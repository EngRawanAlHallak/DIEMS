<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Events\GetSlotsTimelineAction;
use App\Actions\Admin\RequestsScreen\SubmitCompanyAmendmentAction;
use App\Actions\Admin\RequestsScreen\VerifyCompanyAmendmentAction;
use App\Actions\company\CreateProductAction;
use App\Actions\Company\DeleteProductAction;
use App\Actions\Company\DeletePromotionAction;
use App\Actions\Company\GetAvailableSlotsTimelineAction;
use App\Actions\Company\GetCompanyDashboardStatsAction;
use App\Actions\Company\GetCompanyProductsAction;
use App\Actions\Company\GetCompanyProfileAction;
use App\Actions\Company\GetCompanyPromotionsAction;
use App\Actions\Company\GetCompanyRequestsAction;
use App\Actions\Company\GetGlobalSettingsAction;
use App\Actions\Company\PromoteApprovedRequestsAction;
use App\Actions\Company\StoreCompanyRequestAction;
use App\Actions\Company\StoreEventRequestAction;
use App\Actions\Company\StorePromotionAction;
use App\Actions\Company\UpdateCompanyProfileAction;
use App\Actions\Company\UpdatePromotionAction;
use App\Actions\Company\UploadMediaToS3Action;
use App\Actions\Company\UpdateProductAction;
use App\Actions\Payment\InitiateCompanyPaymentAction;
use App\Actions\Payment\InitiateRemainingPaymentAction;
use App\Actions\Visitor\HomePage\GetSectorsAction;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\StoreEventRequest;
use App\Http\Requests\Company\StoreProductRequest;
use App\Http\Requests\Company\StorePromotionRequest;
use App\Http\Requests\Company\UpdateCompanyProfileRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Requests\Company\UpdateProductRequest;
use App\Http\Requests\Company\UpdatePromotionRequest;
use App\Http\Resources\CompanyProfileResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PromotionResource;
use App\Http\Resources\SectorResource;
use App\Models\Payment;
use App\Services\CompanyRequestService;
use App\Services\PaymeraService;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{

    protected $requestService;
    use ApiResponse;

    public function __construct(CompanyRequestService $requestService)
    {
        $this->requestService = $requestService;
    }
    ///function for upload files to aws3
    public function upload(Request $request, UploadMediaToS3Action $uploadAction)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,mp4|max:20480',
        ]);

        $path = $uploadAction->execute(
            $request->file('file'),
            'main_pages' // أو خليه ديناميكي
        );

        $url = Storage::disk('s3')->url($path);

        return response()->json([
            'message' => 'File uploaded successfully',
            'path' => $path,
            'url' => $url,
        ]);
    }

    //function to bring the info of first page on company web
    public function getFirstPage(Request $request, GetGlobalSettingsAction $action): JsonResponse
    {
        try {
            // نأخذ اللغة من الـ Header (مثلاً: Accept-Language: en) أو نعتمد العربي كافتراضي
            $lang = $request->header('lang', 'ar');

            $settings = $action->execute($lang);

            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => ($lang == 'ar') ? 'تم جلب البيانات بنجاح' : 'Settings retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }



    //function to return form details
    public function getFormDependencies(): JsonResponse
    {
        $data = $this->requestService->getRegistrationFormData();

        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    ///function to store company request
    public function storeCompanyRequest(StoreCompanyRequest $request, StoreCompanyRequestAction $action): JsonResponse
    {
        try {
            // تنفيذ الأكشن المعتمد على الـ Transaction والحسابات المالية
            $uploadedDocuments = $request->all()['documents'] ?? [];
            //dd($uploadedDocuments);
            // 2. استدعاء الأكشن وتمرير المصفوفة الكاملة له
            $companyRequest = $action->execute($request->validated(), $uploadedDocuments);
            return response()->json([
                'status'  => 'success',
                'message' => 'تم استلام طلبكم بنجاح. سيصلكم بريد إلكتروني بتفاصيل الدفع.',
                'data'    => [
                    'id'               => $companyRequest->id,
                    'total_price'      => $companyRequest->total_price,
                    'required_deposit' => $companyRequest->required_deposit,
                    'payment_due'      => $companyRequest->payment_due_date->format('Y-m-d H:i'),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Company Registration Error: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    ///test function
    public function promoteReadyRequests(PromoteApprovedRequestsAction $action)
    {
        $companies = $action->execute();
        return response()->json([
            'status' => 'success',
            'message' => "تمت ترقية {$companies->count()} شركة بنجاح.",
            'data' => $companies
        ]);
    }

    //function to update company profile
    public function updateProfile(UpdateCompanyProfileRequest $request, UpdateCompanyProfileAction $action)
    {
        $company = auth()->user()->company;
        $userData = $request->only(['email', 'phonenumber']);
        if ($request->has('name')) {
            $userData['name'] = $request->name;
        }

        $companyData = $request->only(['bio', 'name','address']);

        $updatedCompany = $action->execute(
            $company,
            $userData,
            $companyData,
            $request->file('logo')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات الملف الشخصي بنجاح',
            'data' => $updatedCompany
        ]);
    }

    ////////////////////////Products functions///////////////////////////////
    /// function to store product
    public function storeProduct(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        try {
            $product = $action->execute(
                $request->validated(),
                $request->file('images') ?? [],
                auth()->user()->company
            );

            return response()->json([
                'status' => 'success',
                'message' => 'تم إضافة المنتج وصوره بنجاح',
                // استخدام الريسورس لمنتج واحد
                'data' => new ProductResource($product->load('images'))
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], in_array($e->getCode(), [403, 401]) ? $e->getCode() : 400);
        }
    }

    /**
     * تحديث منتج موجود
     */
    public function updateProduct(UpdateProductRequest $request, $id, UpdateProductAction $action): JsonResponse
    {
        try {
            $company = auth()->user()->company;
            $product = $company->products()->findOrFail($id);

            $updatedProduct = $action->execute(
                $product,
                $request->validated(),
                $company,
                $request->file('new_images') ?? [],
                $request->input('delete_images') ?? []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث المنتج بنجاح، جاري معالجة الصور الجديدة.',
                // استخدام الريسورس هنا أيضاً
                'data' => new ProductResource($updatedProduct->load('images'))
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], in_array($e->getCode(), [403, 401]) ? $e->getCode() : 400);
        }
    }

    /**
     * جلب كافة منتجات الشركة
     */
    public function getProduct(GetCompanyProductsAction $action): JsonResponse
    {
        $company = auth()->user()->company;

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'ملف الشركة غير موجود.'
            ], 404);
        }

        $products = $action->execute($company);

        return response()->json([
            'status' => 'success',
            // استخدام collection لأننا نرجع قائمة منتجات
            'data' => ProductResource::collection($products->load('images'))
        ]);
    }

    /**
     * حذف منتج
     */
    public function destroyProduct($id, DeleteProductAction $action): JsonResponse
    {
        try {
            $company = auth()->user()->company;
            $product = $company->products()->find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المنتج غير موجود.'
                ], 404);
            }

            $action->execute($product);

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف المنتج وكافة صوره بنجاح.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

////////////////////////////promotion functions////////////////////////////////
/// function to create promotion
    public function storePromotion(StorePromotionRequest $request, StorePromotionAction $action): JsonResponse
    {
        try {
            $company = auth()->user()->company;
            $promotion = $action->execute($request->validated(), $company);
            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء العرض بنجاح وتطبيقه على المنتجات المختارة.',
                'data' => new PromotionResource($promotion->load('products'))            ], 201);

        } catch (Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], in_array($e->getCode(), [403, 401, 422]) ? $e->getCode() : 400);
        }
    }
    //function to update promotion
    public function updatePromotion(UpdatePromotionRequest $request, $id, UpdatePromotionAction $action): JsonResponse
    {
        try {
            $promotion = auth()->user()->company->promotions()->findOrFail($id);

            // تنفيذ التحديث بناءً على الحقول المرسلة فقط
            $updatedPromotion = $action->execute($promotion, $request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'تم تحديث العرض بنجاح.',
                'data'    => new PromotionResource($updatedPromotion->load('products'))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    //function for delete peomotion
    /**
     * حذف عرض محدد
     */
    public function destroyPromotion($id, DeletePromotionAction $action): JsonResponse
    {
        try {
            $promotion = auth()->user()->company->promotions()->find($id);

            if (!$promotion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'العرض غير موجود أو لا تملك صلاحية حذفه.'
                ], 404);
            }
            $action->execute($promotion);

            return response()->json([
                'status'  => 'success',
                'message' => 'تم حذف العرض بنجاح.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء محاولة الحذف: ' . $e->getMessage()
            ], 400);
        }
    }
    //function to get company promotions

    public function getPromotions(GetCompanyPromotionsAction $action): JsonResponse
    {
        try {
            $company = auth()->user()->company;

            if (!$company) {
                return response()->json(['status' => 'error', 'message' => 'ملف الشركة غير موجود.'], 404);
            }

            $promotions = $action->execute($company);

            return response()->json([
                'status' => 'success',
                'data' => PromotionResource::collection($promotions)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    //////////////////////home page for company
    public function getCompanyDashboard(GetCompanyDashboardStatsAction $action): JsonResponse
    {
        try {
            $company = auth()->user()->company;

            if (!$company) {
                return response()->json(['status' => 'error', 'message' => 'ملف الشركة غير موجود.'], 404);
            }

            $stats = $action->execute($company);

            return response()->json([
                'status' => 'success',
                'data'   => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    //////////////////function to get company profile info
    public function getCompanyProfile(GetCompanyProfileAction $action): JsonResponse
    {
        try {
            $company = $action->execute();

            // تمرير الموديل للـ Resource ليتولى هو صياغة الـ JSON
            return response()->json([
                'status' => 'success',
                'data'   => new CompanyProfileResource($company)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }
    ///////////////////////////////////events
    /// function to store event request
    public function storeEventRequest(StoreEventRequest $request, StoreEventRequestAction $action): JsonResponse
    {
        try {
            $eventRequest = $action->execute(
                $request->validated(),
                $request->file('image')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'تم استلام طلب تنظيم الفعالية بنجاح، جاري معالجة بياناتكم وسيصلكم بريد إلكتروني لتأكيد الحجز.',
                'data'    => [
                    'request'    => $eventRequest
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Event Request Handling Failed: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
////////////////////////توابع بدي اياهم للشركة مشان الفعاليات
    public function getSectors(GetSectorsAction $action): JsonResponse
    {
        $lang = app()->getLocale();
        $sectors = $action->execute($lang);
        return $this->success(SectorResource::collection($sectors), 'Sectors fetched successfully');
    }

    public function timeline(GetAvailableSlotsTimelineAction $action): JsonResponse
    {
        $timeline = $action->execute();
        return $this->success($timeline, 'Events TimeLine fetched successfully');

    }

    ////////////////////////////////////////////////////////for update the company request (action_required status)

    public function verifyURL(Request $request, VerifyCompanyAmendmentAction $action): JsonResponse
    {
        // الميدل وير 'signed' تكفل بالتحقق من صحة الرابط وانتهائه
        $requestId = $request->query('request');
        $companyRequest = $action->execute($requestId);
        return $this->success($companyRequest, 'Request details fetched for amendment.');
    }

    /**
     * حفظ التعديلات وإعادة الطلب للمراجعة
     */
    public function updateCompanyRequest(UpdateCompanyRequest $request, SubmitCompanyAmendmentAction $action): JsonResponse
    {
        try {
            // استخراج مصفوفة الملفات إن وجدت (بنفس طريقة الـ Store)
            $uploadedDocuments = $request->all()['documents'] ?? [];

            // تمرير الـ ID، البيانات المفلترة، والملفات إلى الـ Action
            $requestId = $request->query('request');
            $companyRequest = $action->execute($requestId, $request->validated(), $uploadedDocuments);

            return $this->success(
                ['id'             => $companyRequest->id,
                    'request_status' => $companyRequest->request_status],
                'تم تحديث طلبكم بنجاح وهو الآن قيد المراجعة من قبل الإدارة.');

        } catch (\Exception $e) {
            Log::error("Company Amendment Error (Request ID: {$requestId}): " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ غير متوقع أثناء معالجة طلب التعديل.'
            ], 500);
        }
    }

    //////////////////////////////////////////////////////////////////////  payment
    /*public function payCompany(int $id, InitiateCompanyPaymentAction $action): JsonResponse
    {
        $result = $action->execute($id);

        return $this->success($result, 'Payment fetched successfully.');
    }*/

    public function payDirectFromEmail(int $id, InitiateCompanyPaymentAction $action)
    {
        try {
            // استدعاء الأكشن للحصول على رابط بوابة الدفع
            $paymeraData = $action->execute($id);
            //return $paymeraUrl;
            $paymeraUrl = is_array($paymeraData) ? $paymeraData['payment_url'] : $paymeraData;
            return redirect()->away($paymeraUrl);

        } catch (\Exception $e) {
            Log::error("[1-Click Payment Error] " . $e->getMessage());

            // في حال الخطأ، أو الدفع المسبق، نعيده لصفحة خطأ في الفرونت إند
            //$errorUrl = config('app.frontend_url') . "/payment/error?message=" . urlencode($e->getMessage());
            //return redirect()->away($errorUrl);
        }
    }

    public function myRequests(GetCompanyRequestsAction $action): JsonResponse
    {
        $company = auth()->user()->company;

        if (!$company) {
            return response()->json(['message' => 'Company profile not found.'], 404);
        }

        $data = $action->execute($company);
        return $this->success($data, 'fetched requests successfully.');

    }
    public function payRemaining(Request $request, InitiateRemainingPaymentAction $action)
    {
        //$companyRequest = auth()->user()->company->companyRequest;
        $request->validate([
            'request_id' => 'required|exists:company_requests,id',
            'amount' => 'required|numeric|min:1'
        ]);

        $result = $action->execute($request->request_id, (float) $request->amount);
        $paymeraUrl = is_array($result) ? $result['payment_url'] : $result;
        //return redirect()->away($paymeraUrl);
        return $this->success($result, 'payment successfully');
    }

    public function verifyPayment(Request $request, PaymeraService $paymeraService)
    {
        $paymentUuid = $request->query('payment_uuid');

        // رابط الفرونت إند الذي نريد توجيه المستخدم إليه في النهاية
        $frontendDashboardUrl = config('app.com_frontend_url') . "companies/dashboard/payments";

        if (!$paymentUuid) {
            return redirect()->away($frontendDashboardUrl . '?status=error&message=' . urlencode('رابط غير صالح أو مفقود.'));
        }

        $payment = Payment::where('uuid', $paymentUuid)->first();

        if (!$payment) {
            return redirect()->away($frontendDashboardUrl . '?status=error&message=' . urlencode('لم يتم العثور على سجل الدفع.'));
        }

        // هنا يتم التحقق الفعلي من بوابه الدفع والتحديث في الداتا بيز (بدون تدخل الفرونت)
        $isPaid = $paymeraService->verifyAndProcessPayment($payment);

        if ($isPaid) {
            // توجيه المتصفح فوراً إلى الفرونت إند مع رسالة نجاح
            return redirect()->away($frontendDashboardUrl . '?status=success&message=' . urlencode('تم تأكيد الدفعة بنجاح.'));
        }

        // توجيه المتصفح إلى الفرونت إند مع رسالة فشل
        return redirect()->away($frontendDashboardUrl . '?status=error&message=' . urlencode('فشلت عملية الدفع أو تم إلغاؤها.'));
    }

}
