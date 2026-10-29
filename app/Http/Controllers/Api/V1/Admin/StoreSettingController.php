<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateStoreSettingsRequest;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;

class StoreSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->respondWith(StoreSetting::current());
    }

    public function update(UpdateStoreSettingsRequest $request): JsonResponse
    {
        $settings = StoreSetting::current();
        $settings->update($request->validated());

        return $this->respondWith($settings->refresh());
    }

    /**
     * Money fields are sent as numbers because the dashboard parses them with `as num?`.
     */
    protected function respondWith(StoreSetting $settings): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'store_name' => $settings->store_name,
                'support_email' => $settings->support_email,
                'support_phone' => $settings->support_phone,
                'currency' => $settings->currency,
                'delivery_fee' => (float) $settings->delivery_fee,
                'free_delivery_threshold' => (float) $settings->free_delivery_threshold,
                'is_store_open' => $settings->is_store_open,
                'order_notifications_enabled' => $settings->order_notifications_enabled,
                'maintenance_mode' => $settings->maintenance_mode,
                'allowed_admin_roles' => $settings->allowed_admin_roles ?? StoreSetting::DEFAULT_ADMIN_ROLES,
                'updated_at' => $settings->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
