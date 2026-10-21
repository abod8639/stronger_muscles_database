<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
            'is_internal_note' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:open,in_progress,waiting_user,resolved,closed'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'نص الرسالة مطلوب',
            'status.in' => 'الحالة المحددة غير صالحة',
        ];
    }
}
