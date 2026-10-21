<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Standard camelCase matching Flutter ReviewModel.fromJson
            'id' => (string) $this->id,
            'productId' => (string) $this->product_id,
            'userId' => (string) $this->user_id,
            'userName' => (string) ($this->user?->name ?? 'Anonymous'),
            'userPhotoUrl' => $this->user?->photo_url,
            'comment' => (string) $this->comment,
            'rating' => (float) $this->rating,
            'isVerifiedPurchase' => (bool) $this->is_verified_purchase,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),

            // Snake case aliases for flexibility
            'product_id' => (string) $this->product_id,
            'user_id' => (string) $this->user_id,
            'user_name' => (string) ($this->user?->name ?? 'Anonymous'),
            'user_photo_url' => $this->user?->photo_url,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
