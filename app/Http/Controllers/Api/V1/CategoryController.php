<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of active categories.
     */
    public function index()
    {
        $categories = $this->categoryService->getActiveCategories();

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
        $category = $this->categoryService->getActiveCategory($id);

        return response()->json([
            'status' => 'success',
            'data' => new CategoryResource($category),
        ]);
    }
}
