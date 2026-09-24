<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function findById(string $id): ?Order
    {
        return Order::with(['user', 'orderItems'])->find($id);
    }

    public function findOrFail(string $id): Order
    {
        return Order::with(['user', 'orderItems'])->findOrFail($id);
    }

    public function createOrderWithItems(array $orderData, array $itemsData): Order
    {
        return DB::transaction(function () use ($orderData, $itemsData) {
            $order = Order::create($orderData);

            foreach ($itemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            return $order;
        });
    }

    public function updateOrderStatus(Order $order, string $status, ?string $paymentStatus = null, ?string $trackingNumber = null): Order
    {
        $attributes = ['status' => $status];
        if ($paymentStatus !== null) {
            $attributes['payment_status'] = $paymentStatus;
        }
        if ($trackingNumber !== null) {
            $attributes['tracking_number'] = $trackingNumber;
        }

        $order->update($attributes);

        return $order->fresh(['user', 'orderItems']);
    }
}
