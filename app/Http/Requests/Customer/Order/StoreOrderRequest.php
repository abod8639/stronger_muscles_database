<?php

namespace App\Http\Requests\Customer\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'address_id' => 'required|exists:addresses,id',
            'notes' => 'nullable|string',
            'shipping_fee' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selected_flavor' => 'nullable|string',
            'items.*.selected_size' => 'nullable|string',
            'payment_method' => 'nullable|string|in:cash,card,paypal,stripe',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_id.required' => 'عنوان التوصيل مطلوب',
            'address_id.exists' => 'عنوان التوصيل المحدد غير صالح',
            'items.required' => 'يجب أن يحتوي الطلب على عناصر',
            'items.min' => 'يجب اختيار منتج واحد على الأقل',
            'items.*.product_id.required' => 'معرف المنتج مطلوب',
            'items.*.product_id.exists' => 'أحد المنتجات المختارة غير موجود',
            'items.*.quantity.required' => 'كمية المنتج مطلوبة',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'payment_method.in' => 'طريقة الدفع المحددة غير مدعومة',
        ];
    }
}
