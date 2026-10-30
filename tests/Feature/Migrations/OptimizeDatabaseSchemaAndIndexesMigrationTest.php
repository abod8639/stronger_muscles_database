<?php

use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->artisan('migrate', ['--no-interaction' => true]);
});

it('removes unused columns from categories', function () {
    expect(Schema::hasColumn('categories', 'flavors'))->toBeFalse()
        ->and(Schema::hasColumn('categories', 'size'))->toBeFalse();
});

it('has all optimized performance indexes', function (string $table, string $index) {
    expect(Schema::hasIndex($table, $index))->toBeTrue();
})->with([
    ['categories', 'categories_is_active_sort_order_index'],
    ['products', 'products_brand_id_index'],
    ['products', 'products_average_rating_index'],
    ['products', 'products_is_active_featured_index'],
    ['products', 'products_is_active_best_seller_index'],
    ['products', 'products_is_active_new_arrival_index'],
    ['promos', 'promos_is_active_created_at_index'],
    ['brands', 'brands_is_active_name_index'],
    ['order_items', 'order_items_variant_id_index'],
    ['cart_items', 'cart_items_variant_id_index'],
]);

it('has variant_id column on order_items and cart_items', function (string $table) {
    expect(Schema::hasColumn($table, 'variant_id'))->toBeTrue()
        ->and(Schema::hasIndex($table, "{$table}_variant_id_index"))->toBeTrue();
})->with([
    'order_items',
    'cart_items',
]);

it('can be rolled back and re-applied cleanly', function () {
    $migration = require database_path('migrations/2026_10_30_090000_optimize_database_schema_and_indexes.php');

    $migration->down();

    expect(Schema::hasColumn('categories', 'flavors'))->toBeTrue()
        ->and(Schema::hasColumn('order_items', 'variant_id'))->toBeFalse()
        ->and(Schema::hasIndex('products', 'products_brand_id_index'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumn('categories', 'flavors'))->toBeFalse()
        ->and(Schema::hasColumn('order_items', 'variant_id'))->toBeTrue()
        ->and(Schema::hasIndex('products', 'products_brand_id_index'))->toBeTrue();
});
