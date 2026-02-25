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
        // Skip if already exists to avoid duplicate index errors
        $connection = Schema::getConnection();

        // Add indexes using raw SQL for better SQLite compatibility
        try {
            if ($connection->getDriverName() === 'sqlite') {
                // SQLite doesn't give us good control, so we use raw SQL
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_addresses_user_id ON addresses(user_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_product_variants_product_id ON product_variants(product_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_orders_address_id ON orders(address_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_cart_items_user_id ON cart_items(user_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_cart_items_product_id ON cart_items(product_id)');
                $connection->statement('CREATE INDEX IF NOT EXISTS idx_categories_parent_id ON categories(parent_id)');
            } else {
                // For other databases, use Schema builder
                Schema::table('addresses', function (Blueprint $table) {
                    $table->index('user_id');
                });

                Schema::table('product_variants', function (Blueprint $table) {
                    $table->index('product_id');
                });

                Schema::table('orders', function (Blueprint $table) {
                    $table->index('address_id');
                });

                Schema::table('order_items', function (Blueprint $table) {
                    $table->index('order_id');
                    $table->index('product_id');
                });

                Schema::table('cart_items', function (Blueprint $table) {
                    $table->index('user_id');
                    $table->index('product_id');
                });

                Schema::table('categories', function (Blueprint $table) {
                    $table->index('parent_id')->nullable();
                });
            }
        } catch (\Exception $e) {
            // Silently fail if indexes already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            try {
                $connection->statement('DROP INDEX IF EXISTS idx_addresses_user_id');
                $connection->statement('DROP INDEX IF EXISTS idx_product_variants_product_id');
                $connection->statement('DROP INDEX IF EXISTS idx_orders_address_id');
                $connection->statement('DROP INDEX IF EXISTS idx_order_items_order_id');
                $connection->statement('DROP INDEX IF EXISTS idx_order_items_product_id');
                $connection->statement('DROP INDEX IF EXISTS idx_cart_items_user_id');
                $connection->statement('DROP INDEX IF EXISTS idx_cart_items_product_id');
                $connection->statement('DROP INDEX IF EXISTS idx_categories_parent_id');
            } catch (\Exception $e) {
                // Silently continue if index removal fails
            }
        } else {
            Schema::table('addresses', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
            });

            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropIndex(['product_id']);
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['address_id']);
            });

            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex(['order_id']);
                $table->dropIndex(['product_id']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropIndex(['product_id']);
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex(['parent_id']);
            });
        }
    }
};
