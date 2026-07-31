<?php

namespace App\Actions\Admin\Tickets;

use App\Models\ExhibitionProfile;
use App\Models\GateCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateDailyGateCodeAction
{
    public function execute()
    {
        $profile = ExhibitionProfile::findOrFail(1);

        // إلغاء تفعيل أي كود سابق لليوم (لحماية النظام إذا ضغط الأدمن مرتين)
        GateCode::where('valid_for_date', today())->update(['is_active' => false]);

        $gateCode = GateCode::Create(
            [
            'code' => strtoupper(Str::random(10)), // 10 حروف وأرقام عشوائية
            'valid_for_date' => today(),
            'starts_at' => $profile->open_time,
            'ends_at' => $profile->close_time,
            'is_active' => true,
        ]);

        return[
            'code' => $gateCode->code
        ];
    }
}
