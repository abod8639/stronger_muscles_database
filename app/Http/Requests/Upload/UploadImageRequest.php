<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'ملف الصورة مطلوب',
            'image.image' => 'الملف يجب أن يكون صورة صالحة',
            'image.mimes' => 'نوع الصورة غير مدعوم (المسموح: jpeg, png, jpg, gif, webp)',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت',
        ];
    }
}
