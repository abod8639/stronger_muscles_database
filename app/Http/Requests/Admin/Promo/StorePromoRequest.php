<?php

namespace App\Http\Requests\Admin\Promo;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'array'],
            'subtitle' => ['nullable', 'array'],
            'button_text' => ['nullable', 'array'],
            'image_url' => ['required', 'string'],
            'background_color' => ['required', 'string'],
            'target_type' => ['nullable', 'string', 'in:none,product,brand'],
            'target_id' => ['nullable', 'string'],
            'target_url' => ['nullable', 'string'],
            'is_active' => ['boolean'],
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
            'image_url.required' => 'رابط صورة الإعلان مطلوب',
            'background_color.required' => 'لون الخلفية مطلوب',
            'target_type.in' => 'نوع الهدف يجب أن يكون none أو product أو brand',
        ];
    }
}
