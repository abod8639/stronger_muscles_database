<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->artisan('migrate', ['--no-interaction' => true]);
});

it('drops indexes that duplicate another index', function (string $table, string $index) {
    expect(Schema::hasIndex($table, $index))->toBeFalse();
})->with([
    ['users', 'users_email_index'],
    ['cart_items', 'idx_cart_items_user_id'],
    ['order_items', 'idx_order_items_order_id'],
    ['order_items', 'idx_order_items_product_id'],
    ['reviews', 'reviews_product_id_index'],
    ['notifications', 'notifications_target_type_index'],
    ['orders', 'orders_user_id_index'],
]);

it('keeps an index leading with every foreign key column', function (string $table, string $column) {
    $hasLeadingIndex = collect(Schema::getIndexes($table))
        ->contains(fn (array $index): bool => $index['columns'][0] === $column);

    expect($hasLeadingIndex)->toBeTrue();
})->with([
    ['users', 'email'],
    ['cart_items', 'user_id'],
    ['cart_items', 'product_id'],
    ['order_items', 'order_id'],
    ['order_items', 'product_id'],
    ['reviews', 'product_id'],
    ['notifications', 'target_type'],
    ['orders', 'user_id'],
    ['orders', 'address_id'],
    ['support_tickets', 'order_id'],
]);

it('serves the customer order list from the composite index', function () {
    $plan = collect(DB::select('EXPLAIN QUERY PLAN select * from orders where user_id = ? order by order_date desc', [1]))
        ->pluck('detail')
        ->implode(' ');

    expect($plan)->toContain('orders_user_id_order_date_index')
        ->not->toContain('TEMP B-TREE');
});

it('can be rolled back and re-applied', function () {
    $migration = require database_path('migrations/2026_10_28_093524_optimize_redundant_and_missing_indexes.php');

    $migration->down();

    expect(Schema::hasIndex('orders', 'orders_user_id_index'))->toBeTrue()
        ->and(Schema::hasIndex('orders', 'orders_user_id_order_date_index'))->toBeFalse();

    $migration->up();

    expect(Schema::hasIndex('orders', 'orders_user_id_index'))->toBeFalse()
        ->and(Schema::hasIndex('orders', 'orders_user_id_order_date_index'))->toBeTrue();
});
