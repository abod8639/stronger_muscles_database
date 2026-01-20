<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'imageUrl' => $category->image_url,
                'sortOrder' => (int) $category->sort_order,
                'isActive' => (bool) $category->is_active,
                'createdAt' => $category->created_at ? $category->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function show(string $id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatCategory($category)
        ]);
    }

    // Write methods removed for Public Controller

    protected function formatCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'imageUrl' => $category->image_url,
            'sortOrder' => (int) $category->sort_order,
            'isActive' => (bool) $category->is_active,
            'createdAt' => $category->created_at ? $category->created_at->toIso8601String() : null,
            'icon' => $category->icon,
        ];
    }
}
