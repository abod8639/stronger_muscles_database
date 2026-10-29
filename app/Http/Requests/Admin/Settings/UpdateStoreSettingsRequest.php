<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'store_name' => 'sometimes|required|string|max:255',
            'support_email' => 'sometimes|nullable|email|max:255',
            'support_phone' => 'sometimes|nullable|string|max:32',
            'currency' => 'sometimes|required|string|max:8',
            'delivery_fee' => 'sometimes|required|numeric|min:0',
            'free_delivery_threshold' => 'sometimes|required|numeric|min:0',
            'is_store_open' => 'sometimes|required|boolean',
            'order_notifications_enabled' => 'sometimes|required|boolean',
            'maintenance_mode' => 'sometimes|required|boolean',
            'allowed_admin_roles' => 'sometimes|required|array|min:1',
            'allowed_admin_roles.*' => 'string|in:'.implode(',', [
                Admin::ROLE_SUPER_ADMIN,
                Admin::ROLE_ADMIN,
                Admin::ROLE_INVENTORY_MANAGER,
                Admin::ROLE_CUSTOMER_SUPPORT,
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.required' => 'اسم المتجر مطلوب',
            'support_email.email' => 'البريد الإلكتروني للدعم غير صالح',
            'delivery_fee.numeric' => 'رسوم التوصيل يجب أن تكون رقماً',
            'delivery_fee.min' => 'رسوم التوصيل لا يمكن أن تكون سالبة',
            'free_delivery_threshold.numeric' => 'حد التوصيل المجاني يجب أن يكون رقماً',
            'free_delivery_threshold.min' => 'حد التوصيل المجاني لا يمكن أن يكون سالباً',
            'allowed_admin_roles.min' => 'يجب السماح بدور واحد على الأقل',
            'allowed_admin_roles.*.in' => 'دور المشرف غير صالح',
        ];
    }
}
