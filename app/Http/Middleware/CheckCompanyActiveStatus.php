<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyActiveStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // التحقق مما إذا كان المستخدم يملك شركة غير نشطة
        if ($user && $user->company && !$user->company->is_active) {
            return response()->json([
                'message' => 'Your company account has been deactivated by the administrator.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

}
