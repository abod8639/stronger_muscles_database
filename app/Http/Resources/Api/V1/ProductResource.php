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
            'name' => (string) $this->name,
            'price' => (double) $this->price,
            'discountPrice' => $this->discount_price ? (double) $this->discount_price : null,
            'imageUrls' => $this->image_urls ?? [],
            'description' => (string) $this->description,
            'categoryId' => (string) $this->category_id,
            'categoryName' => $this->whenLoaded('category', fn() => $this->category->name),
            'stockQuantity' => (int) $this->stock_quantity,
            'averageRating' => (double) $this->average_rating,
            'reviewCount' => (int) $this->review_count,
            'brand' => (string) $this->brand,
            'servingSize' => (string) $this->serving_size,
            'servingsPerContainer' => (int) $this->servings_per_container,
            'is_active' => (bool) $this->is_active,
            'sku' => (string) $this->sku,
            'tags' => $this->tags ?? [],
            'weight' => (double) $this->weight,
            'size' => $this->size ?? [],
            'flavors' => $this->flavors ?? [],
            'nutritionFacts' => $this->nutrition_facts,
            'ingredients' => $this->ingredients ?? [],
            'featured' => (bool) $this->featured,
            'newArrival' => (bool) $this->new_arrival,
            'bestSeller' => (bool) $this->best_seller,
            'totalSales' => (int) $this->total_sales,
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'is_background_white' => (bool) $this->is_background_white,
        ];
    }
}
