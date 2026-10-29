<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Aggregates for the dashboard home and sidebar, computed in the database so the
 * dashboard no longer has to download every order and product to count them.
 */
class DashboardController extends Controller
{
    /**
     * Order, revenue, user and stock totals for the dashboard home page.
     */
    public function stats(): JsonResponse
    {
        $ordersByStatus = Order::query()
            ->selectRaw('status, count(*) as orders_count, sum(total_amount) as revenue')
            ->groupBy('status')
            ->get()
            ->toBase()
            ->keyBy('status');

        $stock = Product::query()
            ->selectRaw('sum(case when stock_quantity > ? then 1 else 0 end) as in_stock', [Product::LOW_STOCK_THRESHOLD])
            ->selectRaw('sum(case when stock_quantity > 0 and stock_quantity <= ? then 1 else 0 end) as low_stock', [Product::LOW_STOCK_THRESHOLD])
            ->selectRaw('sum(case when stock_quantity <= 0 then 1 else 0 end) as out_of_stock')
            ->selectRaw('count(*) as total_products')
            ->first();

        $countFor = fn (string $status): int => (int) ($ordersByStatus[$status]->orders_count ?? 0);

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_revenue' => (float) $ordersByStatus->except('cancelled')->sum('revenue'),
                'total_orders' => (int) $ordersByStatus->sum('orders_count'),
                'total_users' => User::query()->count(),
                'total_products' => (int) $stock->total_products,
                'pending_orders' => $countFor('pending'),
                'processing_orders' => $countFor('processing'),
                'shipped_orders' => $countFor('shipped'),
                'delivered_orders' => $countFor('delivered'),
                'cancelled_orders' => $countFor('cancelled'),
                'in_stock' => (int) $stock->in_stock,
                'low_stock' => (int) $stock->low_stock,
                'out_of_stock' => (int) $stock->out_of_stock,
            ],
        ]);
    }

    /**
     * Counts shown as badges next to the dashboard's sidebar items.
     *
     * Admins have no notification inbox (notifications are sent to customers),
     * so unread_notifications is always 0.
     */
    public function navigationBadges(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'pending_orders' => Order::query()->where('status', 'pending')->count(),
                'unread_notifications' => 0,
                'open_tickets' => SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count(),
                'low_stock_products' => Product::query()->where('stock_quantity', '<=', Product::LOW_STOCK_THRESHOLD)->count(),
            ],
        ]);
    }
}
