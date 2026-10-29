<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shaped for the Flutter app's WishlistItemModel, which reads camelCase keys and string ids.
 */
class WishlistItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $firstImage = collect($product?->image_urls ?? [])->first();

        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'productId' => (string) $this->product_id,
            'addedAt' => $this->created_at?->toIso8601String(),
            'productName' => is_array($product?->name)
                ? ($product->name['ar'] ?? $product->name['en'] ?? null)
                : $product?->name,
            'productPrice' => $product ? (float) ($product->discount_price ?? $product->price) : null,
            'productImageUrl' => is_array($firstImage)
                ? ($firstImage['medium'] ?? $firstImage['original'] ?? null)
                : $firstImage,
        ];
    }
}
