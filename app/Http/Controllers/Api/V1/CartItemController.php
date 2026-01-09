<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartItemController extends Controller
{
    /**
     * Display a listing of the user's cart items.
     */
    public function index(Request $request)
    {
        return response()->json($request->user()->cartItems);
    }

    /**
     * Store a newly created cart item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_name' => 'required|string',
            'price' => 'required|numeric',
            'image_urls' => 'nullable|array',
            'quantity' => 'required|integer|min:1',
            'flavors' => 'nullable|array',
            'size' => 'nullable|array',
        ]);

        $cartItem = $request->user()->cartItems()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $validated['product_id'],
            'product_name' => $validated['product_name'],
            'price' => $validated['price'],
            'image_urls' => $validated['image_urls'],
            'quantity' => $validated['quantity'],
            'flavors' => $validated['flavors'] ?? [],
            'size' => $validated['size'] ?? [],
            'added_at' => now(),
        ]);

        return response()->json($cartItem, 201);
    }

    /**
     * Update the specified cart item.
     */
    public function update(Request $request, string $id)
    {
        $cartItem = $request->user()->cartItems()->findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem->update($validated);

        return response()->json($cartItem);
    }

    /**
     * Remove the specified cart item.
     */
    public function destroy(Request $request, string $id)
    {
        $cartItem = $request->user()->cartItems()->findOrFail($id);
        $cartItem->delete();

        return response()->json(null, 204);
    }
}
