<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        // If authenticated, get user's orders. If not (Dashboard context), get all orders.
        if ($request->user()) {
            $orders = $request->user()->orders()->with('orderItems')->latest()->get();
        } else {
            $orders = Order::with('orderItems')->latest()->get();
        }
        
        return response()->json($orders);
    }

    /**
     * Store a newly created order (Simplified for now).
     */
    public function store(Request $request)
    {
        return response()->json(['message' => 'Order processing not implemented yet'], 501);
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()) {
            $order = $request->user()->orders()->with('orderItems.product')->findOrFail($id);
        } else {
            $order = Order::with('orderItems.product')->findOrFail($id);
        }
        
        return response()->json($order);
    }
}
