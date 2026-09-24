<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
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
    public function store(StoreProductRequest $request)
    {
        Log::info('Product Info Store Request:', $request->all());

        $validated = $request->validated();

        // Normalize image_urls: accept strings or objects
        if (isset($validated['image_urls'])) {
            $validated['image_urls'] = collect($validated['image_urls'])->map(function ($img) {
                if (is_string($img)) {
                    return ['thumbnail' => $img, 'medium' => $img, 'original' => $img];
                }

                return $img;
            })->values()->toArray();
        }

        // Ensure product_sizes is properly passed even if empty
        if ($request->has('product_sizes')) {
            $validated['product_sizes'] = $request->input('product_sizes') ?: [];
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
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);

        Log::info("Product Info Update Request for ID {$id}:", $request->all());

        $validated = $request->validated();

        // Normalize image_urls
        if (isset($validated['image_urls'])) {
            $validated['image_urls'] = collect($validated['image_urls'])->map(function ($img) {
                if (is_string($img)) {
                    return ['thumbnail' => $img, 'medium' => $img, 'original' => $img];
                }

                return $img;
            })->values()->toArray();
        }

        // Ensure product_sizes is properly passed even if empty
        if ($request->has('product_sizes')) {
            $validated['product_sizes'] = $request->input('product_sizes') ?: [];
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
