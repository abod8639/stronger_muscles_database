<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح. يرجى تسجيل الدخول أولاً.',
            ], 401);
        }

        if ($user instanceof \App\Models\Admin) {
            if (isset($user->is_active) && ! $user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'الحساب الإداري معطل.',
                ], 403);
            }

            return $next($request);
        }

        if (isset($user->role) && in_array($user->role, ['admin', 'super_admin'], true)) {
            if (isset($user->is_active) && ! $user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'الحساب معطل.',
                ], 403);
            }

            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'غير مصرح بالوصول إلى هذا القسم الإداري.',
        ], 403);
    }
}
