<?php

namespace App\Actions\Company;

use App\Models\User;
use App\Models\Company;
use App\Models\Sector;
use App\Models\CompanyRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PromoteApprovedRequestsAction
{
    public function execute(CompanyRequest $request): array
    {
        return DB::transaction(function () use ($request) {
            Log::info("in PromoteApprovedRequestsAction ");
            // التحقق من عدم وجود الإيميل مسبقاً لتفادي تضارب البيانات
            if (User::withTrashed()->where('email', $request->email)->exists()) {
                throw ValidationException::withMessages([
                    'status' => ["Skipping request {$request->id}: Email already exists."],
                ]);
            }

            $sector = Sector::where('name->ar', $request->sector)
                ->orWhere('name->en', $request->sector)
                ->first();

            // توليد كلمة مرور عشوائية قوية
            $plainPassword = Str::password(10, true, true, true, false);

            // 1. إنشاء المستخدم للشركة
            $user = User::create([
                'name'        => $request->getTranslation('company_name', 'en', false) ?? $request->getTranslation('company_name', 'ar'),
                'email'       => $request->email,
                'phonenumber' => $request->phone,
                'password'    => Hash::make($plainPassword),
            ]);

            $user->assignRole('company');

            // 2. إنشاء الشركة الفعلية
            $newCompany = Company::create([
                'user_id'            => $user->id,
                'name'               => $request->getTranslations('company_name'),
                'bio'                => $request->getTranslations('company_description'),
                'nationality'        => $request->getTranslations('nationality'),
                'responsible_person' => $request->responsible_name,
                'sector'             => $request->sector,
                'sector_id'          => $sector ? $sector->id : 1,
                'address'            => $request->getTranslations('address'),
                'final_area'         => $request->requested_area,
                'booth_type'         => $request->setup_preference,
                'is_active'          => true,
            ]);

            // ربط الطلب بالشركة
            $request->update(['company_id' => $newCompany->id]);

            // 3. نقل تبعية الوثائق المرفوعة من "الطلب" إلى "الشركة"
            if ($request->documents()->exists()) {
                $request->documents()->update([
                    'documentable_id'   => $newCompany->id,
                    'documentable_type' => Company::class,
                ]);
            }

            Log::info("done PromoteApprovedRequestsAction ");
            // إرجاع المستخدم وكلمة المرور غير المشفرة لإرسالها بالإيميل
            return [
                'user'     => $user,
                'password' => $plainPassword
            ];
        });
        /*$pendingPromotions = CompanyRequest::where('request_status', 'approved')
            ->whereIn('payment_status', ['paid', 'partial_paid'])
            ->whereDoesntHave('company')
            ->get();

        $promotedCompanies = collect();

        foreach ($pendingPromotions as $request) {
            try {
                $company = DB::transaction(function () use ($request) {

                    if (User::withTrashed()->where('email', $request->email)->exists()) {
                        Log::warning("Skipping request {$request->id}: Email already exists.");
                        return null;
                    }

                    $sector = Sector::where('name->ar', $request->sector)
                        ->orWhere('name->en', $request->sector)
                        ->first();

                    // إنشاء المستخدم للشركة
                    $user = User::create([
                        'name'        => $request->company_name,
                        'email'       => $request->email,
                        'phonenumber' => $request->phone,
                        'password'    => Hash::make($request->phone),
                    ]);

                    $user->assignRole('company');

                    // إنشاء الشركة الفكرية
                    $newCompany = Company::create([
                        'user_id'            => $user->id,
                        //'company_request_id' => $request->id,
                        'name'               => $request->getTranslations('company_name'),
                        'bio'                => $request->getTranslations('company_description'),
                        'nationality'        => $request->getTranslations('nationality'),
                        'responsible_person' => $request->responsible_name,
                        'sector'             => $request->sector,
                        'sector_id'          => $sector ? $sector->id : 1,
                        'address'            => $request->getTranslations('address'),
                        'final_area'         => $request->requested_area,
                        'booth_type'         => $request->setup_preference,
                        'is_active'          => true,
                    ]);

                    $request->update(['company_id' => $newCompany->id]);

                    // ─── التعديل الجديد للوثائق والملفات ───────────────────────
                    // نقل تبعية الوثائق المرفوعة من "الطلب" إلى "الشركة" مباشرة
                    if ($request->documents()->exists()) {
                        $request->documents()->update([
                            'documentable_id'   => $newCompany->id,
                            'documentable_type' => Company::class, // سيتحول لـ App\Models\Company
                        ]);
                    }
                    // ───────────────────────────────────────────────────────────

                    return $newCompany;
                });

                if ($company) {
                    $promotedCompanies->push($company);
                }

            } catch (\Exception $e) {
                Log::error("Error promoting request ID {$request->id}: " . $e->getMessage());
            }
        }

        return $promotedCompanies;*/
    }
}
