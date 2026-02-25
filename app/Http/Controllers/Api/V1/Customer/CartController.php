<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cartItems = $request->user()
            ->cartItems()
            ->with(['product:id,name,price,discount_price,stock_quantity,image_urls,brand'])
            ->latest('added_at')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'image_urls' => $item->image_urls,
                    'quantity' => $item->quantity,
                    'flavors' => $item->flavors,
                    'size' => $item->size,
                    'added_at' => $item->added_at,
                    'total_price' => $item->getTotalPriceAttribute(),
                    'product' => $item->product,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $cartItems,
            'grand_total' => $cartItems->sum('total_price'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Security: Fetch price from database, not from client request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|string|exists:products,id',
            'quantity' => 'required|integer|min:1|max:999',
            'flavors' => 'nullable|array',
            'size' => 'nullable|array',
        ]);

        // Fetch product with stock check
        $product = Product::active()
            ->inStock()
            ->select(['id', 'name', 'price', 'discount_price', 'image_urls', 'stock_quantity'])
            ->findOrFail($validated['product_id']);

        // Security: Verify quantity doesn't exceed stock
        if ($validated['quantity'] > $product->stock_quantity) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested quantity exceeds available stock',
            ], 422);
        }

        // Get the correct price (use discount_price if available)
        $price = ($product->discount_price > 0 && $product->discount_price < $product->price)
            ? $product->discount_price
            : $product->price;

        // Check if item already in cart
        $existingItem = $request->user()
            ->cartItems()
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem->quantity + $validated['quantity'];

            if ($newQuantity > $product->stock_quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Total quantity exceeds available stock',
                ], 422);
            }

            $existingItem->update([
                'quantity' => $newQuantity,
                'added_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Cart item updated',
                'data' => $existingItem->load('product'),
            ], 200);
        }

        // Create new cart item
        $cartItem = $request->user()->cartItems()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $validated['product_id'],
            'product_name' => $product->name,
            'price' => $price,
            'image_urls' => $product->image_urls,
            'quantity' => $validated['quantity'],
            'flavors' => $validated['flavors'] ?? [],
            'size' => $validated['size'] ?? [],
            'added_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Item added to cart',
            'data' => $cartItem->load('product'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cartItem = $request->user()
            ->cartItems()
            ->findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        // Verify stock availability
        $product = $cartItem->product;
        if ($validated['quantity'] > $product->stock_quantity) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested quantity exceeds available stock',
            ], 422);
        }

        $cartItem->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item updated',
            'data' => $cartItem->fresh(['product']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $cartItem = $request->user()
            ->cartItems()
            ->findOrFail($id);

        $cartItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Item removed from cart',
        ], 204);
    }

    /**
     * Clear all cart items for the user
     */
    public function clearCart(Request $request)
    {
        $request->user()->cartItems()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart cleared successfully',
        ]);
    }
}
