<?php

namespace App\Http\Requests\Customer\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'التقييم مطلوب',
            'rating.numeric' => 'يجب أن يكون التقييم رقماً',
            'rating.min' => 'الحد الأدنى للتقييم هو 1',
            'rating.max' => 'الحد الأقصى للتقييم هو 5',
            'comment.required' => 'نص التعليق مطلوب',
            'comment.string' => 'يجب أن يكون التعليق نصاً صالحاً',
            'comment.max' => 'يجب ألا يتجاوز التعليق 1000 حرف',
        ];
    }
}
