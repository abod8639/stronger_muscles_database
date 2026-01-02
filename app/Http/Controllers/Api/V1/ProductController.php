<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true);

        if ($request->has('category') && $request->category != null) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search') && $request->search != null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function ($product) {
            return [
                'id' => (string) $product->id, // تأكيد تحويل المعرف لنص
                'name' => (string) $product->name,
                'price' => (double) $product->price,
                'discountPrice' => $product->discount_price ? (double) $product->discount_price : null,
                'imageUrls' => is_array($product->image_urls) ? $product->image_urls : [],
                'description' => (string) $product->description,
                'categoryId' => (string) $product->category_id,
                'stockQuantity' => (int) $product->stock_quantity,
                'averageRating' => (double) $product->average_rating,
                'reviewCount' => (int) $product->review_count,
                'brand' => (string) $product->brand,
                'servingSize' => (string) $product->serving_size,
                'servingsPerContainer' => (int) $product->servings_per_container,
                'isActive' => (bool) $product->is_active,
                'createdAt' => $product->created_at ? $product->created_at->toIso8601String() : null,
                'updatedAt' => $product->updated_at ? $product->updated_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
