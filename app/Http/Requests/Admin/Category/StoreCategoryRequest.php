<?php

namespace App\Http\Requests\Admin\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('id') || empty($this->input('id'))) {
            $nameEn = $this->input('name.en');
            $nameAr = $this->input('name.ar');
            $base = $nameEn ?: $nameAr;
            $id = Str::slug($base ?: 'category');

            $originalId = $id;
            $count = 1;
            while (Category::where('id', $id)->exists()) {
                $id = $originalId.'-'.$count++;
            }

            $this->merge(['id' => $id]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'unique:categories,id'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'image_url' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'معرف التصنيف مطلوب',
            'id.unique' => 'معرف التصنيف مستخدم بالفعل',
            'name.required' => 'اسم التصنيف مطلوب',
            'name.ar.required' => 'الاسم العربي للتصنيف مطلوب',
            'parent_id.exists' => 'التصنيف الرئيسي المحدد غير موجود',
        ];
    }
}
