<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_id' => ['nullable', 'exists:admins,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_id.exists' => 'المشرف المحدد غير موجود',
        ];
    }
}
