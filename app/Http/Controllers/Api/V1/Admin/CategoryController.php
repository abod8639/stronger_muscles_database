<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->boolean('tree')) {
            $query->whereNull('parent_id');
        }

        $categories = $query->get()->map(fn ($category) => $this->formatCategory($category));

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        $category = Category::create($validated);

        $this->clearCaches();

        return response()->json([
            'status' => 'success',
            'data' => $this->formatCategory($category),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatCategory($category),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validated();

        $category->update($validated);

        $this->clearCaches();

        return response()->json([
            'status' => 'success',
            'data' => $this->formatCategory($category),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete category with associated products',
            ], 422);
        }

        $category->delete();

        $this->clearCaches();

        return response()->json(null, 204);
    }

    protected function formatCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name, // Object {ar: ..., en: ...}
            'description' => $category->description, // Object
            'imageUrl' => $category->image_url,
            'sortOrder' => (int) $category->sort_order,
            'isActive' => (bool) $category->is_active,
            'createdAt' => $category->created_at ? $category->created_at->toIso8601String() : null,
            'icon' => $category->icon,
            'parentId' => $category->parent_id,
            'children' => $category->children->map(fn ($child) => $this->formatCategory($child)),
        ];
    }

    protected function clearCaches()
    {
        \Illuminate\Support\Facades\Cache::forget('categories:active:list');
        \Illuminate\Support\Facades\Cache::forget('shop_categories_list');
        \Illuminate\Support\Facades\Cache::forget('categories_list');
        \Illuminate\Support\Facades\Cache::forget('categories_tree');
    }
}
