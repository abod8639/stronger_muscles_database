<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get user statistics and order details.
     */
    public function index()
    {
        $users = User::with(['orders.orderItems.product'])->get();

        $stats = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'photo_url' => $user->photo_url,
                'has_ordered' => $user->orders->isNotEmpty(),
                'orders_count' => $user->orders->count(),
                'orders' => $user->orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'status' => $order->status,
                        'total_amount' => $order->total_amount,
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'product_name' => $item->product ? $item->product->name : $item->product_name, // Fallback if product deleted
                                'quantity' => $item->quantity,
                                'image_url' => $item->product ? ($item->product->image_urls[0] ?? null) : $item->image_url,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json([
            'total_users' => $users->count(),
            'users' => $stats,
        ]);
    }
}
