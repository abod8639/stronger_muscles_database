<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Cart\AddToCartRequest;
use App\Http\Requests\Customer\Cart\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cart = $this->cartService->getUserCart($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $cart['items'],
            'grand_total' => $cart['grand_total'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Security: Fetch price from database, not from client request
     */
    public function store(AddToCartRequest $request)
    {
        $validated = $request->validated();

        $cartItem = $this->cartService->addToCart($request->user(), $validated);

        $status = $cartItem->wasRecentlyCreated ? 201 : 200;
        $message = $cartItem->wasRecentlyCreated ? 'Item added to cart' : 'Cart item updated';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $cartItem->load('product'),
        ], $status);
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
    public function update(UpdateCartItemRequest $request, string $id)
    {
        $validated = $request->validated();

        $cartItem = $this->cartService->updateCartItemQuantity($request->user(), $id, $validated['quantity']);

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
        $this->cartService->removeFromCart($request->user(), $id);

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
        $this->cartService->clearCart($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Cart cleared successfully',
        ]);
    }
}
