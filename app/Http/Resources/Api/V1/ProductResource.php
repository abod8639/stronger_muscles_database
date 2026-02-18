<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,        // {"ar": "...", "en": "..."}
            'description' => $this->description, // {"ar": "...", "en": "..."}
            'brand' => (string) ($this->brand ?? ''),

            // Category as object
            'category' => $this->whenLoaded('category', fn () => [
                'id' => (string) $this->category->id,
                'name' => $this->category->name,
            ]),

            // Images: support both stored-as-string and stored-as-object
            'imageUrls' => collect($this->image_urls ?? [])->map(fn ($img) => [
                'thumbnail' => is_array($img) ? ($img['thumbnail'] ?? $img['original'] ?? $img) : $img,
                'medium' => is_array($img) ? ($img['medium'] ?? $img['original'] ?? $img) : $img,
                'original' => is_array($img) ? ($img['original'] ?? $img) : $img,
            ])->values(),

            // Variants
            'has_variants' => $this->whenLoaded('variants', fn () => $this->variants->isNotEmpty(), false),
            'product_variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            // Pricing
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,

            // Stock & Ratings
            'stock_quantity' => (int) $this->stock_quantity,
            'average_rating' => (float) $this->average_rating,
            'review_count' => (int) $this->review_count,

            // Nutritional
            'serving_size' => $this->serving_size,
            'servings_per_container' => (int) $this->servings_per_container,
            'nutrition_facts' => $this->nutrition_facts,

            // Attributes
            'flavors' => $this->flavors ?? [],
            'product_sizes' => $this->product_sizes ?? [],
            'size' => $this->size ?? [],
            'tags' => $this->tags ?? [],
            'weight' => $this->weight ? (float) $this->weight : null,

            // Flags
            'is_active' => (bool) $this->is_active,
            'is_background_white' => (bool) $this->is_background_white,
            'featured' => (bool) $this->featured,
            'new_arrival' => (bool) $this->new_arrival,
            'best_seller' => (bool) $this->best_seller,

            // Meta
            'sku' => $this->sku,
            'total_sales' => (int) $this->total_sales,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
