<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Actions\GateStuff\AuthenticateGateStaffAction;
use App\Actions\GateStuff\ScanTicketQrAction;
use Illuminate\Validation\ValidationException;

class GateScannerController extends Controller
{
    use ApiResponse;
    public function authenticate(Request $request, AuthenticateGateStaffAction $action): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10']
        ]);

        $action->execute($validated['code']);
        return $this->success( 'AUTHENTICATED');
    }

    public function scanTicket(Request $request, ScanTicketQrAction $action): JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'uuid'] // نتحقق من الصيغة كأول خطوة حماية
        ]);

        $data = $action->execute($validated['qr_code']);
        return $data;
    }
}
