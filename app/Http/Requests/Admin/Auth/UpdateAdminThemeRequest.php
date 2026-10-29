<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminThemeRequest extends FormRequest
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
            'mode' => 'required|string|in:light,dark,system',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.required' => 'وضع المظهر مطلوب',
            'mode.in' => 'وضع المظهر يجب أن يكون light أو dark أو system',
        ];
    }
}
