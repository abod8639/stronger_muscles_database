<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

use App\Http\Resources\Api\V1\ProductResource;

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
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%");
        }

        $products = $query->with('category')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(true)
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
            'is_background_white' => 'nullable|boolean',
        ]);

        $product = Product::create($validated);
        
        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product->load('category'))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product)
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
            'is_background_white' => 'nullable|boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product->load('category'))
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ], 204);
    }
}
