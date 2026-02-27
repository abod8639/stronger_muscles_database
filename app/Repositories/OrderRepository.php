<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function createOrderWithItems(array $orderData, array $itemsData)
    {
        return DB::transaction(function () use ($orderData, $itemsData) {
            $order = Order::create($orderData);
            
            foreach ($itemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }
            
            return $order;
        });
    }
}
