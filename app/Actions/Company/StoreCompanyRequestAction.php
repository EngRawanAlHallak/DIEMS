<?php
namespace App\Actions\Company;

use App\Actions\General\TranslateTextAction;
use App\Jobs\Company\UploadCompanyDocuments;
use App\Jobs\Notification\SendAdminNotificationJob;
use App\Models\CompanyRequest;
use App\Models\PricingTier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreCompanyRequestAction
{
    protected $translator;
    public function __construct(TranslateTextAction $translator)
    {
        $this->translator = $translator;
    }

    public function execute(array $data, array $files = []): CompanyRequest
    {
        return DB::transaction(function () use ($data, $files) {

            $tier = PricingTier::where('company_type', $data['foreign_local'])
                ->where('slug', $data['setup_preference'])
                ->firstOrFail();

            if ($tier->min_area && $data['requested_area'] < $tier->min_area) {
                throw new \Exception("المساحة المطلوبة أقل من الحد الأدنى لهذا النوع ({$tier->min_area} متر مربع).");
            }

            $totalPrice = $data['requested_area'] * $tier->unit_price;
            $requiredDeposit = $totalPrice * 0.25;

            $translatedName = $this->translator->execute($data['company_name']);
            $translatedDescription = $this->translator->execute($data['company_description']);
            $translatedNationality = $this->translator->execute($data['nationality']);
            $translatedAddress = $this->translator->execute($data['address']);

            // 1. إنشاء طلب الشركة
            $companyRequest = CompanyRequest::create([
                'foreign_local'       => $data['foreign_local'],
                'company_name'        => $translatedName,
                'responsible_name'    => $data['responsible_name'],
                'job_title'           => $data['job_title'],
                'email'               => $data['email'],
                'phone'               => $data['phone'],
                'nationality'         => $translatedNationality,
                'commercial_register' => $data['commercial_register'],
                'address'             => $translatedAddress,
                'sector'              => $data['sector'],
                'company_description' => $translatedDescription,
                'requested_area'      => $data['requested_area'],
                'setup_preference'    => $data['setup_preference'],
                'terms_accepted_at'   => now(),
                'total_price'         => $totalPrice,
                'required_deposit'    => $requiredDeposit,
                'payment_due_date'    => Carbon::now()->addHours(48),
            ]);

            // 2. معالجة مصفوفة الملفات الديناميكية (تم إصلاح اسم المتغير هنا إلى $files)
            $tempFiles = [];

            if (!empty($files) && is_array($files)) {
                foreach ($files as $docData) {
                    // التأكد من أن المفاتيح المرسلة تحتوي على الملف والنوع بشكل صحيح
                    if (isset($docData['file']) && $docData['file']->isValid() && isset($docData['type'])) {
                        $file = $docData['file'];

                        // تخزين مؤقت محلي وسريع في السيرفر الداخلي
                        $tempPath = $file->store('temp', 'local');

                        $tempFiles[] = [
                            'temp_path'     => $tempPath,
                            'file_type'     => $docData['type'] ?? 'other_document',
                            'original_name' => $file->getClientOriginalName() // تأكدي من إرسال هذا الحقل ليقرأه الـ Job
                        ];
                    }
                }
            }

            // 3. إطلاق الـ Job إذا كانت المصفوفة غير فارغة
            if (!empty($tempFiles)) {
                // للتجربة المباشرة ورؤية الأخطاء فوراً يمكنك استخدام dispatchSync مؤقتاً
                UploadCompanyDocuments::dispatch($companyRequest, $tempFiles);
            }

            $companyNameEn = $data['company_name']['en'] ?? ($data['company_name']['ar'] ?? 'A new company');
            SendAdminNotificationJob::dispatch([
                'title'  => 'New Company Request',
                'body'   => "{$companyNameEn} has submitted a new joining request.",
                'type'   => 'company_request',
                'sender' => 'company',
            ]);

            return $companyRequest;
        });
    }
}
