<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\StoreBrandRequest;
use App\Http\Requests\Admin\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $brandService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $brands = $this->brandService->getAllBrands()->map(fn ($brand) => $this->formatBrand($brand));

        return response()->json([
            'status' => 'success',
            'data' => $brands,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request)
    {
        $validated = $request->validated();

        $brand = $this->brandService->createBrand($validated);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatBrand($brand),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $brand = $this->brandService->getBrand($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatBrand($brand),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, string $id)
    {
        $validated = $request->validated();

        $brand = $this->brandService->updateBrand($id, $validated);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatBrand($brand),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->brandService->deleteBrand($id);

            return response()->json(null, 204);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    protected function formatBrand(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'displayName' => $brand->display_name,
            'slug' => $brand->slug,
            'imageUrl' => $brand->image_url,
            'isActive' => (bool) $brand->is_active,
            'productsCount' => $brand->products()->count(),
            'createdAt' => $brand->created_at ? $brand->created_at->toIso8601String() : null,
        ];
    }

    protected function clearCaches()
    {
        \Illuminate\Support\Facades\Cache::forget('brands:list');
    }
}
