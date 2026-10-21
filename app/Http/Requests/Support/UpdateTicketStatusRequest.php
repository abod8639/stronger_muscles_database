<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:open,in_progress,waiting_user,resolved,closed'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'حالة التذكرة مطلوبة',
            'status.in' => 'حالة التذكرة غير صالحة',
        ];
    }
}
