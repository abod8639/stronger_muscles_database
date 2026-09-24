<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'id' => 'required|string|unique:products,id',
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'description' => 'required|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image_urls' => 'nullable|array',
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_background_white' => 'nullable|boolean',
            'serving_size' => 'nullable|string',
            'servings_per_container' => 'nullable|integer|min:0',
            'flavors' => 'nullable|array',
            'product_sizes' => 'nullable|array',
            'product_sizes.*.size' => 'required|string',
            'product_sizes.*.price' => 'required|numeric|min:0',
            'product_sizes.*.discount_price' => 'nullable|numeric|min:0',
            'size' => 'nullable|array',
            'product_variants' => 'nullable|array',
            'variants' => 'nullable|array',
            'brand_id' => 'nullable|string|exists:brands,id',
            'sku' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0',
            'nutrition_facts' => 'nullable|array',
            'ingredients' => 'nullable|array',
            'featured' => 'nullable|boolean',
            'new_arrival' => 'nullable|boolean',
            'best_seller' => 'nullable|boolean',
            'shipping_weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'usage_instructions' => 'nullable|array',
            'warnings' => 'nullable|array',
            'expiry_date' => 'nullable|date',
            'manufacturer' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'معرف المنتج مطلوب',
            'id.unique' => 'معرف المنتج مستخدم بالفعل',
            'name.required' => 'اسم المنتج مطلوب',
            'price.required' => 'سعر المنتج مطلوب',
            'category_id.required' => 'تصنيف المنتج مطلوب',
            'category_id.exists' => 'التصنيف المحدد غير موجود',
        ];
    }
}
