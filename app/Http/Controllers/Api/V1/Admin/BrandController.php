<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $brands = Brand::orderBy('created_at', 'desc')->get()->map(fn ($brand) => $this->formatBrand($brand));

        return response()->json([
            'status' => 'success',
            'data' => $brands,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'slug' => 'nullable|string|unique:brands,slug',
        ]);

        if (empty($validated['slug'])) {
            $base = $validated['name']['en'] ?: $validated['name']['ar'];
            $validated['slug'] = Str::slug($base) . '-' . Str::random(5);
        }

        $brand = Brand::create($validated);

        $this->clearCaches();

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
        $brand = Brand::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatBrand($brand),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|array',
            'name.ar' => 'nullable|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'slug' => 'nullable|string|unique:brands,slug,' . $brand->id,
        ]);

        $brand->update($validated);

        $this->clearCaches();

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
        $brand = Brand::findOrFail($id);

        if ($brand->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete brand with associated products',
            ], 422);
        }

        $brand->delete();

        $this->clearCaches();

        return response()->json(null, 204);
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
