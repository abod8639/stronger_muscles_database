<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inserts rows that point at missing records, the way the old schema allowed.
 */
function seedOrphanedRows(): void
{
    Schema::withoutForeignKeyConstraints(function () {
        $now = now();

        DB::table('users')->insert(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('addresses')->insert(['id' => 7, 'user_id' => 1, 'street' => 'Main St', 'city' => 'Cairo', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('users')->where('id', 1)->update(['default_address_id' => '404']);
        DB::table('categories')->insert(['id' => 'protein', 'name' => '{"ar":"بروتين","en":"Protein"}', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('products')->insert(['id' => 'PROD-1', 'name' => '{"ar":"واي","en":"Whey"}', 'price' => 100, 'description' => '{}', 'category_id' => 'protein', 'created_at' => $now, 'updated_at' => $now]);

        $order = ['user_id' => 1, 'order_date' => $now, 'subtotal' => 100, 'total_amount' => 100, 'created_at' => $now, 'updated_at' => $now];
        DB::table('orders')->insert([
            ['id' => 'ORD-VALID', 'address_id' => '7', ...$order],
            ['id' => 'ORD-STALE', 'address_id' => 'addr-gone', ...$order],
        ]);

        $item = ['product_name' => 'Whey', 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'created_at' => $now, 'updated_at' => $now];
        DB::table('order_items')->insert([
            ['id' => 'ITEM-OK', 'order_id' => 'ORD-VALID', 'product_id' => 'PROD-1', ...$item],
            ['id' => 'ITEM-DELETED-PRODUCT', 'order_id' => 'ORD-VALID', 'product_id' => 'PROD-GONE', ...$item],
            ['id' => 'ITEM-NO-ORDER', 'order_id' => 'ORD-GONE', 'product_id' => 'PROD-1', ...$item],
        ]);

        $cartItem = ['product_name' => 'Whey', 'price' => 100, 'created_at' => $now, 'updated_at' => $now];
        DB::table('cart_items')->insert([
            ['id' => 'CART-OK', 'user_id' => '1', 'product_id' => 'PROD-1', ...$cartItem],
            ['id' => 'CART-NO-USER', 'user_id' => '999', 'product_id' => 'PROD-1', ...$cartItem],
            ['id' => 'CART-NO-PRODUCT', 'user_id' => '1', 'product_id' => 'PROD-GONE', ...$cartItem],
        ]);

        DB::table('support_tickets')->insert(['id' => 1, 'ticket_number' => 'TKT-1', 'user_id' => 1, 'name' => 'Owner', 'email' => 'owner@example.com', 'subject' => 'Help', 'order_id' => 12345, 'created_at' => $now, 'updated_at' => $now]);
    });
}

beforeEach(function () {
    $this->artisan('migrate', ['--no-interaction' => true]);

    $this->migration = require database_path('migrations/2026_10_28_084506_add_missing_foreign_keys_and_fix_column_types.php');
    $this->migration->down();
    seedOrphanedRows();
    $this->migration->up();
});

it('removes order items whose order no longer exists', function () {
    expect(DB::table('order_items')->pluck('id')->sort()->values()->all())
        ->toBe(['ITEM-DELETED-PRODUCT', 'ITEM-OK']);
});

it('keeps order items whose product was deleted but clears the product reference', function () {
    expect(DB::table('order_items')->where('id', 'ITEM-DELETED-PRODUCT')->value('product_id'))->toBeNull()
        ->and(DB::table('order_items')->where('id', 'ITEM-OK')->value('product_id'))->toBe('PROD-1');
});

it('removes cart items whose user or product no longer exists', function () {
    expect(DB::table('cart_items')->pluck('id')->all())->toBe(['CART-OK']);
});

it('converts valid address references and clears dangling ones', function () {
    expect(DB::table('orders')->where('id', 'ORD-VALID')->value('address_id'))->toBe(7)
        ->and(DB::table('orders')->where('id', 'ORD-STALE')->value('address_id'))->toBeNull()
        ->and(DB::table('users')->where('id', 1)->value('default_address_id'))->toBeNull();
});

it('clears support ticket order references that never matched an order', function () {
    expect(DB::table('support_tickets')->where('id', 1)->value('order_id'))->toBeNull();
});

it('leaves no foreign key violations behind', function () {
    expect(DB::select('PRAGMA foreign_key_check'))->toBeEmpty();
});

it('can be rolled back and re-applied', function () {
    $this->migration->down();

    expect(Schema::getForeignKeys('order_items'))->toBeEmpty()
        ->and(Schema::getColumnType('products', 'name'))->toBe('varchar');

    $this->migration->up();

    expect(Schema::getForeignKeys('order_items'))->toHaveCount(2);
});
