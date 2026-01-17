<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartItem;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json($request->user()->cartItems);
    }

    /**
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not typically used for individual cart items via API, usually handled in index/update/destroy
        // But for resource consistency:
        // return $request->user()->cartItems()->findOrFail($id);
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        // Parameter check logic for $id vs $request used in Laravel resource controllers
        $cartItem = $request->user()->cartItems()->findOrFail($id);
        $cartItem->delete();

        return response()->json(null, 204);
    }
}
