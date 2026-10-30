<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop misplaced columns and add performance index to categories
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'flavors')) {
                $table->dropColumn('flavors');
            }
            if (Schema::hasColumn('categories', 'size')) {
                $table->dropColumn('size');
            }
            if (! Schema::hasIndex('categories', ['is_active', 'sort_order'])) {
                $table->index(['is_active', 'sort_order'], 'categories_is_active_sort_order_index');
            }
        });

        // 2. Add performance indexes to products table
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasIndex('products', ['brand_id'])) {
                $table->index('brand_id', 'products_brand_id_index');
            }
            if (! Schema::hasIndex('products', ['average_rating'])) {
                $table->index('average_rating', 'products_average_rating_index');
            }
            if (! Schema::hasIndex('products', ['is_active', 'featured'])) {
                $table->index(['is_active', 'featured'], 'products_is_active_featured_index');
            }
            if (! Schema::hasIndex('products', ['is_active', 'best_seller'])) {
                $table->index(['is_active', 'best_seller'], 'products_is_active_best_seller_index');
            }
            if (! Schema::hasIndex('products', ['is_active', 'new_arrival'])) {
                $table->index(['is_active', 'new_arrival'], 'products_is_active_new_arrival_index');
            }
        });

        // 3. Add performance index to promos table
        Schema::table('promos', function (Blueprint $table) {
            if (! Schema::hasIndex('promos', ['is_active', 'created_at'])) {
                $table->index(['is_active', 'created_at'], 'promos_is_active_created_at_index');
            }
        });

        // 4. Add performance index to brands table
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasIndex('brands', ['is_active', 'name'])) {
                $table->index(['is_active', 'name'], 'brands_is_active_name_index');
            }
        });

        // 5. Add optional variant reference to order_items
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'variant_id')) {
                $table->string('variant_id')->nullable()->after('product_id');
                $table->index('variant_id', 'order_items_variant_id_index');
            }
        });

        // 6. Add optional variant reference to cart_items
        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'variant_id')) {
                $table->string('variant_id')->nullable()->after('product_id');
                $table->index('variant_id', 'cart_items_variant_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'variant_id')) {
                $table->dropIndex('cart_items_variant_id_index');
                $table->dropColumn('variant_id');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'variant_id')) {
                $table->dropIndex('order_items_variant_id_index');
                $table->dropColumn('variant_id');
            }
        });

        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasIndex('brands', 'brands_is_active_name_index')) {
                $table->dropIndex('brands_is_active_name_index');
            }
        });

        Schema::table('promos', function (Blueprint $table) {
            if (Schema::hasIndex('promos', 'promos_is_active_created_at_index')) {
                $table->dropIndex('promos_is_active_created_at_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasIndex('products', 'products_brand_id_index')) {
                $table->dropIndex('products_brand_id_index');
            }
            if (Schema::hasIndex('products', 'products_average_rating_index')) {
                $table->dropIndex('products_average_rating_index');
            }
            if (Schema::hasIndex('products', 'products_is_active_featured_index')) {
                $table->dropIndex('products_is_active_featured_index');
            }
            if (Schema::hasIndex('products', 'products_is_active_best_seller_index')) {
                $table->dropIndex('products_is_active_best_seller_index');
            }
            if (Schema::hasIndex('products', 'products_is_active_new_arrival_index')) {
                $table->dropIndex('products_is_active_new_arrival_index');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasIndex('categories', 'categories_is_active_sort_order_index')) {
                $table->dropIndex('categories_is_active_sort_order_index');
            }
            if (! Schema::hasColumn('categories', 'flavors')) {
                $table->text('flavors')->nullable();
            }
            if (! Schema::hasColumn('categories', 'size')) {
                $table->text('size')->nullable();
            }
        });
    }
};
