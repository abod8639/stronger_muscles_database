<?php

namespace App\Http\Requests\Admin\Notification;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'in:general,promo,order,system'],
            'target_type' => ['required', 'string', 'in:all,user'],
            'user_id' => ['required_if:target_type,user', 'nullable', 'exists:users,id'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'data' => ['nullable', 'array'],
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
            'title.required' => 'عنوان الإشعار مطلوب',
            'title.max' => 'عنوان الإشعار يجب ألا يتجاوز 255 حرفاً',
            'body.required' => 'نص الإشعار مطلوب',
            'target_type.required' => 'نوع الهدف مطلوب',
            'target_type.in' => 'نوع الهدف يجب أن يكون all أو user',
            'user_id.required_if' => 'يجب تحديد المستخدم عند اختيار إرسال لمستخدم محدد',
            'user_id.exists' => 'المستخدم المحدد غير موجود',
            'type.in' => 'نوع الإشعار غير صالح',
        ];
    }
}
