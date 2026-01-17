<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Admin might want to see all products including inactive ones
        $search = $request->query('search');
        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $products = $query->paginate(20)->through(fn($product) => $this->formatProduct($product));

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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
            'size' => 'nullable|array',
        ]);

        $product = Product::create($validated);
        // $this->clearProductCache(); // Cache clearing should ideally happen via observer or service

        return response()->json([
            'status' => 'success',
            'data' => $this->formatProduct($product)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $this->formatProduct($product)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
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
            'size' => 'nullable|array',
        ]);

        $product->update($validated);
        // $this->clearProductCache();

        return response()->json([
            'status' => 'success',
            'data' => $this->formatProduct($product)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        // $this->clearProductCache();

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
}
