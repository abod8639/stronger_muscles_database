<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of active categories.
     */
    public function index()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show(string $id)
    {
        $category = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new CategoryResource($category),
        ]);
    }
}
