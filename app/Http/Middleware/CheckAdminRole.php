<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user();

        if (! $admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك. يرجى تسجيل الدخول أولاً.',
            ], 401);
        }

        if (isset($admin->is_active) && ! $admin->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'تم تعطيل هذا الحساب الإداري.',
            ], 403);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (method_exists($admin, 'hasRole') && $admin->hasRole($roles)) {
            return $next($request);
        }

        if (isset($admin->role)) {
            if ($admin->role === 'super_admin' || in_array($admin->role, $roles, true)) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'ليس لديك الصلاحية الكافية للوصول إلى هذا القسم.',
            'required_roles' => $roles,
        ], 403);
    }
}
