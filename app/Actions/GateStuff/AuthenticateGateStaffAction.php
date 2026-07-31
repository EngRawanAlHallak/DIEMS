<?php

namespace App\Actions\GateStuff;

use App\Models\GateCode;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthenticateGateStaffAction
{
    public function execute(string $code)
    {
        $now = Carbon::now('Asia/Damascus');

        // جلب الرمز الساري لليوم الحالي وفي الوقت الحالي
        $gateCode = GateCode::where('code', $code)
            ->where('is_active', true)
            ->whereDate('valid_for_date', $now->toDateString())
            ->whereTime('starts_at', '<=', $now->toTimeString())
            ->whereTime('ends_at', '>=', $now->toTimeString())
            ->first();

        if (!$gateCode) {
            throw ValidationException::withMessages([
                'code' => 'الرمز المدخل غير صالح أو انتهت صلاحيته لهذا اليوم.'
            ]);
        }
    }
}
