<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.required' => 'أولوية التذكرة مطلوبة',
            'priority.in' => 'مستوى الأولوية غير صالح',
        ];
    }
}
