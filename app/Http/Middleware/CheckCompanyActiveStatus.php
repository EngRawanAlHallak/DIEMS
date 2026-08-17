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

        // 1. التحقق من وجود المستخدم ووجود ملف شركة مرتبط به (لمنع الأدمن أو الزوار)
        if (!$user || !$user->company) {
            return response()->json([
                'message' => 'Unauthorized access. This area is reserved for company accounts only.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 2. التحقق من أن حساب الشركة نشط
        if (!$user->company->is_active) {
            return response()->json([
                'message' => 'Your company account has been deactivated by the administrator.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

}
