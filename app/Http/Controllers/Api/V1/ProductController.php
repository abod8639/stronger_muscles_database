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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:products,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image_urls' => 'nullable|array',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'serving_size' => 'nullable|string|max:255',
            'servings_per_container' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'flavors' => 'nullable|array',
        ]);

        $product = Product::create($validated);
        $this->clearProductCache();

        return response()->json([
            'status' => 'success',
            'data' => $this->formatProduct($product)
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image_urls' => 'nullable|array',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'serving_size' => 'nullable|string|max:255',
            'servings_per_container' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'flavors' => 'nullable|array',
        ]);

        $product->update($validated);
        $this->clearProductCache();

        return response()->json([
            'status' => 'success',
            'data' => $this->formatProduct($product)
        ]);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        $this->clearProductCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ], 204);
    }

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
            'createdAt' => $product->created_at ? $product->created_at->toIso8601String() : null,
            'updatedAt' => $product->updated_at ? $product->updated_at->toIso8601String() : null,
            'flavor' => is_array($product->flavors) ? $product->flavors : [],
        ];
    }

    protected function clearProductCache(): void
    {
        // Simple strategy: clear all products cache keys if possible, 
        // but since we use dynamic keys, we might need a better strategy or just wait for TTL.
        // For now, let's assume we can clear by prefix if a cache driver supports it, 
        // or just accept the 10 min TTL for simplicity in this demo.
        Cache::flush(); // WARNING: This clears ALL cache. In production use tags or specific keys.
    }
}
