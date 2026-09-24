<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$userId,
            'phone' => 'nullable|string|max:20',
            'photo_url' => 'nullable|string|max:500',
            'preferred_language' => 'nullable|string|in:ar,en',
            'notifications_enabled' => 'nullable|boolean',
            'addresses' => 'nullable|array',
            'addresses.*.label' => 'nullable|string|max:50',
            'addresses.*.full_name' => 'nullable|string|max:255',
            'addresses.*.phone' => 'nullable|string|max:20',
            'addresses.*.street' => 'nullable|string|max:255',
            'addresses.*.city' => 'nullable|string|max:100',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.country' => 'nullable|string|max:100',
            'addresses.*.postal_code' => 'nullable|string|max:20',
            'addresses.*.is_default' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'هذا البريد الإلكتروني مسجل مسبقاً',
            'preferred_language.in' => 'اللغة المفضلة يجب أن تكون ar أو en',
        ];
    }
}
