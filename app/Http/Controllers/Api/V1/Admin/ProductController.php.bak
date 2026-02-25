<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['category', 'variants'])->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:products,id',
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'description' => 'required|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image_urls' => 'nullable|array',
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_background_white' => 'nullable|boolean',
            'serving_size' => 'nullable|string',
            'servings_per_container' => 'nullable|integer|min:0',
            'flavors' => 'nullable|array',
            'product_sizes' => 'nullable|array',
            'product_sizes.*.size' => 'required|string',
            'product_sizes.*.price' => 'required|numeric|min:0',
            'product_sizes.*.discount_price' => 'nullable|numeric|min:0',
            'size' => 'nullable|array',
            // Support both 'variants' (legacy) and 'product_variants' (new)
            'product_variants' => 'nullable|array',
            'product_variants.*.sku' => 'required|string|distinct',
            'product_variants.*.price' => 'required|numeric|min:0',
            'product_variants.*.discount_price' => 'nullable|numeric|min:0',
            'product_variants.*.stock_quantity' => 'required|integer|min:0',
            'product_variants.*.attributes' => 'required|array',
            'product_variants.*.is_active' => 'nullable|boolean',
            // Legacy key support
            'variants' => 'nullable|array',
            'variants.*.sku' => 'required_with:variants|string|distinct',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
            'variants.*.attributes' => 'required_with:variants|array',
        ]);

        // Normalize image_urls: accept strings or objects
        if (isset($validated['image_urls'])) {
            $validated['image_urls'] = collect($validated['image_urls'])->map(function ($img) {
                if (is_string($img)) {
                    return ['thumbnail' => $img, 'medium' => $img, 'original' => $img];
                }

                return $img;
            })->values()->toArray();
        }

        $product = Product::create($validated);

        // Support both 'product_variants' and legacy 'variants'
        $variantsData = $request->input('product_variants') ?? $request->input('variants') ?? [];

        foreach ($variantsData as $variantData) {
            $product->variants()->create([
                'id' => (string) Str::uuid(),
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'discount_price' => $variantData['discount_price'] ?? null,
                'discount_start_date' => $variantData['discount_start_date'] ?? null,
                'discount_end_date' => $variantData['discount_end_date'] ?? null,
                'stock_quantity' => $variantData['stock_quantity'],
                'attributes' => $variantData['attributes'],
                'is_active' => $variantData['is_active'] ?? true,
            ]);
        }

        Artisan::call('cache:clear');

        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product->load(['category', 'variants'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with(['category', 'variants'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|array',
            'description' => 'nullable|array',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image_urls' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_background_white' => 'nullable|boolean',
            'serving_size' => 'nullable|string',
            'servings_per_container' => 'nullable|integer|min:0',
            'flavors' => 'nullable|array',
            'product_sizes' => 'nullable|array',
            'size' => 'nullable|array',
            'product_variants' => 'nullable|array',
            'product_variants.*.id' => 'nullable|string',
            'product_variants.*.sku' => 'required_with:product_variants|string',
            'product_variants.*.price' => 'required_with:product_variants|numeric|min:0',
            'product_variants.*.discount_price' => 'nullable|numeric|min:0',
            'product_variants.*.stock_quantity' => 'required_with:product_variants|integer|min:0',
            'product_variants.*.attributes' => 'required_with:product_variants|array',
            'product_variants.*.is_active' => 'nullable|boolean',
            // Legacy
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|string',
            'variants.*.sku' => 'required_with:variants|string',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
            'variants.*.attributes' => 'required_with:variants|array',
        ]);

        // Normalize image_urls
        if (isset($validated['image_urls'])) {
            $validated['image_urls'] = collect($validated['image_urls'])->map(function ($img) {
                if (is_string($img)) {
                    return ['thumbnail' => $img, 'medium' => $img, 'original' => $img];
                }

                return $img;
            })->values()->toArray();
        }

        $product->update($validated);

        // Sync variants (support both keys)
        $variantsData = $request->input('product_variants') ?? $request->input('variants');

        if ($variantsData !== null) {
            $existingIds = collect($variantsData)->pluck('id')->filter()->toArray();
            $product->variants()->whereNotIn('id', $existingIds)->delete();

            foreach ($variantsData as $variantData) {
                $variantId = $variantData['id'] ?? (string) Str::uuid();
                $product->variants()->updateOrCreate(
                    ['id' => $variantId],
                    [
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'discount_price' => $variantData['discount_price'] ?? null,
                        'discount_start_date' => $variantData['discount_start_date'] ?? null,
                        'discount_end_date' => $variantData['discount_end_date'] ?? null,
                        'stock_quantity' => $variantData['stock_quantity'],
                        'attributes' => $variantData['attributes'],
                        'is_active' => $variantData['is_active'] ?? true,
                    ]
                );
            }
        }

        Artisan::call('cache:clear');

        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product->load(['category', 'variants'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        Artisan::call('cache:clear');

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
        ]);
    }
}
