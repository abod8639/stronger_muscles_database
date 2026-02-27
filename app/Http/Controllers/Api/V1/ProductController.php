<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    #[OA\Get(
        path: "/api/v1/products",
        operationId: "getProductsList",
        tags: ["Products"],
        summary: "Get list of products",
        description: "Returns list of products with filtering, searching and sorting",
        parameters: [
            new OA\Parameter(name: "category", in: "query", description: "Filter by category slug", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "search", in: "query", description: "Search by product name", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "sort_by", in: "query", description: "Sort by: latest, price_low, price_high, best_seller, rating, new", required: false, schema: new OA\Schema(type: "string", default: "latest")),
            new OA\Parameter(name: "page", in: "query", description: "Page number", required: false, schema: new OA\Schema(type: "integer", default: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent())
        ]
    )]
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'latest');
        $page = $request->query('page', 1);

        // More fine tuned cache key
        $cacheKey = 'products:list:'.md5("cat={$category}&search={$search}&sort={$sortBy}&page={$page}");

        $products = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($category, $search, $sortBy) {
            $query = Product::active()
                ->forListView()
                ->withCategoryData();

            // Filtering by Category
            if ($category) {
                $query->category($category);
            }

            // Search
            if ($search) {
                $query->search($search);
            }

            // Sorting
            $query = match ($sortBy) {
                'price_low' => $query->sortByPrice('asc'),
                'price_high' => $query->sortByPrice('desc'),
                'best_seller' => $query->sortByPopularity(),
                'rating' => $query->sortByRating(),
                'new' => $query->newArrivals()->latest('created_at'),
                default => $query->orderBy('created_at', 'desc'),
            };

            return $query->paginate(20);
        });

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/products/{id}",
        operationId: "getProductById",
        tags: ["Products"],
        summary: "Get product information",
        description: "Returns product data",
        parameters: [
            new OA\Parameter(name: "id", description: "Product id", required: true, in: "path", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent()),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
    public function show(string $id)
    {
        try {
            // Cache individual product for 60 minutes
            $product = Cache::remember("product:{$id}", now()->addHours(1), function () use ($id) {
                return Product::active()
                    ->forDetailView()
                    ->withCategoryData()
                    ->with('variants:id,product_id,sku,price,discount_price,stock_quantity,attributes')
                    ->findOrFail($id);
            });

            return response()->json([
                'status' => 'success',
                'data' => new ProductResource($product),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found or inactive',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
