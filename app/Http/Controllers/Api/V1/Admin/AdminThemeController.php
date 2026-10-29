<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\UpdateAdminThemeRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Syncs each admin's dashboard theme across their devices.
 */
class AdminThemeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->respondWith($request->user());
    }

    public function update(UpdateAdminThemeRequest $request): JsonResponse
    {
        $admin = $request->user();
        $admin->update([
            'theme_mode' => $request->validated('mode'),
            'theme_updated_at' => now(),
        ]);

        return $this->respondWith($admin);
    }

    protected function respondWith(Admin $admin): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'mode' => $admin->theme_mode ?? 'dark',
                'updated_at' => $admin->theme_updated_at?->toIso8601String(),
            ],
        ]);
    }
}
