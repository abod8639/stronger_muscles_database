<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $cacheKey = "products_index_v1_cat_{$category}_search_{$search}_page_{$page}";

        $products = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($category, $search) {
            $query = Product::where('is_active', true);

            if ($category) {
                $query->where('category_id', $category);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            return $query->paginate(20)->through(fn($product) => $this->formatProduct($product));
        });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatProduct($product)
        ]);
    }

    // Write methods removed for Public Controller

    protected function formatProduct(Product $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => (string) $product->name,
            'price' => (double) $product->price,
            'discountPrice' => $product->discount_price ? (double) $product->discount_price : null,
            'imageUrls' => is_array($product->image_urls) ? $product->image_urls : [],
            'description' => (string) $product->description,
            'categoryId' => (string) $product->category_id,
            'stockQuantity' => (int) $product->stock_quantity,
            'averageRating' => (double) $product->average_rating,
            'reviewCount' => (int) $product->review_count,
            'brand' => (string) $product->brand,
            'servingSize' => (string) $product->serving_size,
            'servingsPerContainer' => (int) $product->servings_per_container,
            'isActive' => (bool) $product->is_active,

            'sku' => (string) $product->sku,
            'tags' => is_array($product->tags) ? $product->tags : [],
            'weight' => (double) $product->weight,
            'size' => is_array($product->size) ? $product->size : [],
            'flavors' => is_array($product->flavors) ? $product->flavors : [],
            'nutrition_facts' => $product->nutrition_facts,
            'ingredients' => is_array($product->ingredients) ? $product->ingredients : [],
            'featured' => (bool) $product->featured,
            'new_arrival' => (bool) $product->new_arrival,
            'best_seller' => (bool) $product->best_seller,
            'total_sales' => (int) $product->total_sales,

            'createdAt' => $product->created_at ? $product->created_at->toIso8601String() : null,
            'updatedAt' => $product->updated_at ? $product->updated_at->toIso8601String() : null,
        ];
    }

    // Cache clearing logic moved to Admin Controller
}
