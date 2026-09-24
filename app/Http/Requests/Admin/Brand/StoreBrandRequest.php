<?php

namespace App\Http\Requests\Admin\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreBrandRequest extends FormRequest
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
        if (! $this->has('slug') || empty($this->input('slug'))) {
            $nameEn = $this->input('name.en');
            $nameAr = $this->input('name.ar');
            $base = $nameEn ?: $nameAr;
            if ($base) {
                $this->merge([
                    'slug' => Str::slug($base).'-'.Str::random(5),
                ]);
            }
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
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'slug' => ['nullable', 'string', 'unique:brands,slug'],
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
            'name.required' => 'اسم العلامة التجارية مطلوب',
            'name.ar.required' => 'الاسم العربي للعلامة التجارية مطلوب',
            'slug.unique' => 'الرابط المخصص (slug) مستخدم بالفعل',
        ];
    }
}
