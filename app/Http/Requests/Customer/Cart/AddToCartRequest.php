<?php

namespace App\Http\Requests\Customer\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
            'product_id' => 'required|string|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'selected_flavor' => 'nullable|string',
            'selected_size' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'معرف المنتج مطلوب',
            'product_id.exists' => 'المنتج المحدد غير موجود',
            'quantity.required' => 'الكمية مطلوبة',
            'quantity.min' => 'يجب اختيار كمية 1 على الأقل',
        ];
    }
}
