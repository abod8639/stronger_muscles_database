<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    /**
     * Display a listing of active products with filtering and sorting.
     */
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'latest'); // default to latest
        $page = $request->query('page', 1);

        $cacheKey = "products_index_v1_cat_{$category}_search_{$search}_sort_{$sortBy}_page_{$page}";

        $products = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($category, $search, $sortBy) {
            $query = Product::query()
                ->where('is_active', true)
                ->with('category');

            // 1. Filtering by Category
            if ($category) {
                $query->where('category_id', $category);
            }

            // 2. Search (Name, Description, Brand)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            }

            // 3. Sorting
            switch ($sortBy) {
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'best_seller':
                    $query->orderBy('total_sales', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('average_rating', 'desc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            return $query->paginate(20);
        });

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(true)
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id)
    {
        try {
            $product = Product::with('category')
                ->where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => new ProductResource($product)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found or inactive'
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }
}

