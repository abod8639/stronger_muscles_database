<?php

namespace App\Http\Requests\Customer\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class StoreWishlistItemRequest extends FormRequest
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
        ];
    }
}
