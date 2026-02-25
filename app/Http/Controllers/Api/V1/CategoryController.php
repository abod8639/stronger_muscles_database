<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Display a listing of active categories.
     */
    public function index()
    {
        $categories = Cache::remember('categories:active:list', now()->addHours(2), function () {
            return Category::active()
                ->ordered()
                ->withProductCount()
                ->forListView()
                ->get();
        });

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
        $category = Cache::remember("category:{$id}", now()->addHours(2), function () use ($id) {
            return Category::active()
                ->withProductCount()
                ->findOrFail($id);
        });

        return response()->json([
            'status' => 'success',
            'data' => new CategoryResource($category),
        ]);
    }
}
