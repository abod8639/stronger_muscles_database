<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = $this->productService->getAdminProducts(10);

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

        if ($request->has('product_sizes')) {
            $validated['product_sizes'] = $request->input('product_sizes') ?: [];
        }

        $variantsData = $request->input('product_variants') ?? $request->input('variants') ?? [];

        $product = $this->productService->createProduct($validated, $variantsData);

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
        $product = $this->productService->getAdminProduct($id);

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
        Log::info("Product Info Update Request for ID {$id}:", $request->all());

        $validated = $request->validated();

        if ($request->has('product_sizes')) {
            $validated['product_sizes'] = $request->input('product_sizes') ?: [];
        }

        $variantsData = $request->input('product_variants') ?? $request->input('variants');

        $product = $this->productService->updateProduct($id, $validated, $variantsData);

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
        $this->productService->deleteProduct($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
        ]);
    }
}
