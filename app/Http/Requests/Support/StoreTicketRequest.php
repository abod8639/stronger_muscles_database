<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'عنوان التذكرة مطلوب',
            'category.required' => 'قسم التذكرة مطلوب',
            'message.required' => 'نص الرسالة أو الشكوى مطلوب',
            'priority.in' => 'مستوى الأولوية غير صالح',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'order_id.exists' => 'الطلب المحدد غير موجود',
        ];
    }
}
