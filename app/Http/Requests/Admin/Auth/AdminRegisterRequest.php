<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminRegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:super_admin,admin,inventory_manager,customer_support',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب للمشرف',
            'email.required' => 'البريد الإلكتروني مطلوب للمشرف',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمشرف آخر',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 أحرف',
            'role.in' => 'الدور المحدد للمشرف غير صالح',
        ];
    }
}
