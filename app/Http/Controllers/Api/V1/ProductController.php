<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    #[OA\Get(
        path: '/api/v1/products',
        operationId: 'getProductsList',
        tags: ['Products'],
        summary: 'Get list of products',
        description: 'Returns list of products with filtering, searching and sorting',
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter by category slug', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by product name', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', description: 'Sort by: latest, price_low, price_high, best_seller, rating, new', required: false, schema: new OA\Schema(type: 'string', default: 'latest')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent),
        ]
    )]
    public function index(Request $request)
    {
        $filters = [
            'category' => $request->query('category'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'latest'),
        ];
        $page = (int) $request->query('page', 1);

        $products = $this->productService->getPublicProducts($filters, 20, $page);

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        operationId: 'getProductById',
        tags: ['Products'],
        summary: 'Get product information',
        description: 'Returns product data',
        parameters: [
            new OA\Parameter(name: 'id', description: 'Product id', required: true, in: 'path', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(string $id)
    {
        try {
            $product = $this->productService->getProductDetails($id);

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
