<?php

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('defines the expected foreign key', function (string $table, string $column, string $foreignTable, string $onDelete) {
    $foreignKey = collect(Schema::getForeignKeys($table))
        ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]);

    expect($foreignKey)->not->toBeNull()
        ->and($foreignKey['foreign_table'])->toBe($foreignTable)
        ->and(strtolower($foreignKey['on_delete']))->toBe($onDelete);
})->with([
    'products.category_id' => ['products', 'category_id', 'categories', 'restrict'],
    'orders.user_id' => ['orders', 'user_id', 'users', 'restrict'],
    'orders.address_id' => ['orders', 'address_id', 'addresses', 'set null'],
    'order_items.order_id' => ['order_items', 'order_id', 'orders', 'cascade'],
    'order_items.product_id' => ['order_items', 'product_id', 'products', 'set null'],
    'cart_items.user_id' => ['cart_items', 'user_id', 'users', 'cascade'],
    'cart_items.product_id' => ['cart_items', 'product_id', 'products', 'cascade'],
    'users.default_address_id' => ['users', 'default_address_id', 'addresses', 'set null'],
    'support_tickets.order_id' => ['support_tickets', 'order_id', 'orders', 'set null'],
]);

it('stores translatable names in text columns', function (string $table, string $column) {
    expect(Schema::getColumnType($table, $column))->toBe('text');
})->with([
    ['products', 'name'],
    ['categories', 'name'],
    ['order_items', 'product_name'],
]);

it('keeps a long arabic product name intact', function () {
    $longName = ['ar' => str_repeat('مكمل بروتين واي معزول ', 20), 'en' => 'Whey Isolate'];

    $product = Product::factory()->create(['name' => $longName]);

    expect($product->fresh()->name)->toBe($longName);
});

it('keeps order item snapshots when their product is deleted', function () {
    $orderItem = OrderItem::factory()->create();
    $cartItem = CartItem::factory()->create(['product_id' => $orderItem->product_id]);

    Product::query()->whereKey($orderItem->product_id)->delete();

    expect($orderItem->fresh())->not->toBeNull()
        ->and($orderItem->fresh()->product_id)->toBeNull()
        ->and($orderItem->fresh()->product_name)->toBe($orderItem->product_name)
        ->and(CartItem::query()->find($cartItem->id))->toBeNull();
});

it('deletes order items together with their order', function () {
    $orderItem = OrderItem::factory()->create();

    Order::query()->whereKey($orderItem->order_id)->delete();

    expect(OrderItem::query()->find($orderItem->id))->toBeNull();
});

it('detaches orders and the default address when an address is deleted', function () {
    $user = User::factory()->create();
    $address = Address::factory()->default()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);
    $order = Order::factory()->create(['user_id' => $user->id, 'address_id' => $address->id]);

    $address->delete();

    expect($order->fresh()->address_id)->toBeNull()
        ->and($order->fresh()->shipping_address_snapshot)->not->toBeNull()
        ->and($user->fresh()->default_address_id)->toBeNull();
});

it('prevents deleting a user who still has orders', function () {
    $order = Order::factory()->create();

    expect(fn () => User::query()->whereKey($order->user_id)->delete())
        ->toThrow(QueryException::class);
});

it('removes cart items when their user is deleted', function () {
    $cartItem = CartItem::factory()->create();

    User::query()->whereKey($cartItem->user_id)->delete();

    expect(CartItem::query()->find($cartItem->id))->toBeNull();
});

it('prevents deleting a category that still has products', function () {
    $product = Product::factory()->create();

    expect(fn () => Category::query()->whereKey($product->category_id)->delete())
        ->toThrow(QueryException::class);
});

it('rejects references to records that do not exist', function (Closure $createInvalidRecord) {
    expect($createInvalidRecord)->toThrow(QueryException::class);
})->with([
    'order for missing user' => fn () => Order::factory()->create(['user_id' => 999_999]),
    'order item for missing order' => fn () => OrderItem::factory()->create(['order_id' => 'ORD-MISSING']),
    'cart item for missing product' => fn () => CartItem::factory()->create(['product_id' => 'PROD-MISSING']),
    'product in missing category' => fn () => Product::factory()->create(['category_id' => 'missing-category']),
]);

it('links support tickets to string order ids and detaches them when the order is deleted', function () {
    $order = Order::factory()->create();
    $ticket = SupportTicket::create([
        'ticket_number' => 'TKT-0001',
        'user_id' => $order->user_id,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'subject' => 'Where is my order?',
        'order_id' => $order->id,
    ]);

    expect($ticket->fresh()->order->id)->toBe($order->id);

    $order->delete();

    expect($ticket->fresh()->order_id)->toBeNull();
});
