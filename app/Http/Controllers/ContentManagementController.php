<?php

namespace App\Http\Controllers;

use App\Actions\Company\GetGlobalSettingsAction;
use App\Actions\ContentManagement\UpdateExhibitionProfileAction;
use App\Actions\ContentManagement\UpdateVisitorAppContentAction;
use App\Actions\ContentManagement\UpdateTransportSettingsAction;
use App\Actions\ContentManagement\SaveTransportAction;
use App\Actions\ContentManagement\DeleteTransportAction;
use App\Actions\ContentManagement\UpdateCompanyPagesAction;
use App\Actions\Visitor\GetProfileAction;
use App\Actions\Visitor\GetTransportationAction;
use App\Actions\Visitor\GetWelcomePageAction;
use App\Models\ExhibitionProfile;
use App\Models\Transportation;
use App\Http\Requests\Contentmanagement\UpdateExhibitionProfileRequest;
use App\Http\Requests\Contentmanagement\UpdateVisitorAppContentRequest;
use App\Http\Requests\Contentmanagement\SaveTransportFormRequest;
use App\Http\Requests\Contentmanagement\UpdateGlobalSettingsRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;

class ContentManagementController extends Controller
{
    use ApiResponse;
    /**
     * 1. تحديث بيانات بروفايل المعرض (ومزامنتها تلقائياً مع بيانات الشركة)
     */
    public function updateExhibitionProfile(
        UpdateExhibitionProfileRequest $request,
        UpdateExhibitionProfileAction $action
    ): JsonResponse {
        $result = $action->execute($request->validated());

        return response()->json($result);
    }

    /**
     * 2. تحديث محتوى شاشة الترحيب الخاصة بتطبيق الزائر (العنوان، الشعار، الفيديو)
     */
    public function updateVisitorAppContent(
        UpdateVisitorAppContentRequest $request,
        UpdateVisitorAppContentAction $action
    ): JsonResponse {
        $result = $action->execute($request->validated());

        return response()->json($result);
    }

    /**
     * 3. حفظ/تحديث بيانات المواصلات من Form واحد بالواجهة
     * يقوم بتحديث الإعدادات العامة (الفاصل الزمني، البداية والنهاية) بجدول المعرض
     * بالإضافة إلى حفظ/تحديث خط المواصلات الفردي بجدول المواصلات
     */

    public function getTransportation(GetTransportationAction $action)
    {
        try{
            $transportations = $action->execute();
            return $this->success($transportations, 'transportation fetched successfully');
        }catch (Exception $exception){
            return $this->error($exception->getMessage());
        }
    }
    public function saveTransportForm(
        SaveTransportFormRequest $request,
        UpdateTransportSettingsAction $updateSettingsAction,
        SaveTransportAction $saveTransportAction,
        ?Transportation $transport = null
    ): JsonResponse {
        $validated = $request->validated();

        $updateSettingsAction->execute(array_filter([
            'transport_interval_minutes' => $validated['transport_interval_minutes'] ?? null,
            'transport_start_time'       => $validated['transport_start_time'] ?? null,
            'transport_end_time'         => $validated['transport_end_time'] ?? null,
        ], fn($value) => !is_null($value)));

        $transportData = array_filter([
            'name'            => $validated['name'] ?? null,
            'google_maps_url' => $validated['google_maps_url'] ?? null,
        ], fn($value) => !is_null($value));

        if ($request->hasFile('image')) {
            $transportData['image'] = $request->file('image');
        }

        $transportResult = $saveTransportAction->execute($transportData, $transport);

        $profile = ExhibitionProfile::first();
        $updatedTransport = $transportResult['data'] ?? $transport;

        // 5. بناء الرد النهائي المكتمل بقيم الداتابيز المضمونة
        return response()->json([
            'success' => true,
            'message' => 'تم الحفظ بنجاح',
            'data'    => [
                'transport_line' => $updatedTransport,
                'settings'       => [
                    'transport_interval_minutes' => $profile?->transport_interval_minutes,
                    'transport_start_time'       => $profile?->transport_start_time,
                    'transport_end_time'         => $profile?->transport_end_time,
                ],
            ],
        ]);
    }
    /**
     * 4. حذف خط مواصلات محدد
     */
    public function deleteTransport(
        Transportation $transport,
        DeleteTransportAction $action
    ): JsonResponse {
        $deletedTransport = $action->execute($transport);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف خط المواصلات بنجاح',
            'data'    => $deletedTransport
        ]);
    }

    /**
     * 5. تحديث كافة بيانات وإعدادات الصفحة الرئيسية والشركة دفعة واحدة (Global Settings)
     */
    public function updateGlobalSettings(
        UpdateGlobalSettingsRequest $request, // الـ Request المناسب
        UpdateCompanyPagesAction $action     // الـ Action المسؤول عن التنفيذ
    ): JsonResponse {
        // تنفيـذ الأكشن
        $result = $action->execute($request->validated());

        // إرجاع الاستجابة الموحدة التي يحسبها BaseAction
        return response()->json($result);
    }

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

    public function getProfile(GetProfileAction $action): JsonResponse
    {
        $data = $action->execute();
        return $this->success($data, 'profile content retrieved.');

    }

    public function welcomePage(GetWelcomePageAction $action): JsonResponse
    {
        $data = $action->execute();
        return $this->success($data, 'Welcome page content retrieved.');

    }
}
