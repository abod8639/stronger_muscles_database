<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops indexes that duplicate another index (or are a prefix of a composite one)
 * and adds the indexes that the customer order list and support tickets query on.
 *
 * Every step is guarded by name or columns so it runs on databases where earlier
 * migrations created the indexes under different names (e.g. MySQL foreign key indexes).
 */
return new class extends Migration
{
    /**
     * Indexes that are fully covered by another index on the same table.
     *
     * @var array<string, list<string>>
     */
    protected array $redundantIndexes = [
        'users' => ['users_email_index'],
        'cart_items' => ['idx_cart_items_user_id'],
        'order_items' => ['idx_order_items_order_id', 'idx_order_items_product_id'],
        'reviews' => ['reviews_product_id_index'],
        'notifications' => ['notifications_target_type_index'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasIndex('orders', ['user_id', 'order_date'])) {
                $table->index(['user_id', 'order_date']);
            }
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasIndex('support_tickets', ['order_id'])) {
                $table->index('order_id');
            }
        });

        $this->dropIndexes([...$this->redundantIndexes, 'orders' => ['orders_user_id_index']]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('user_id', 'idx_cart_items_user_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('product_id', 'idx_order_items_product_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('product_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('target_type');
        });

        $this->dropIndexes([
            'orders' => ['orders_user_id_order_date_index'],
            'support_tickets' => ['support_tickets_order_id_index'],
        ]);
    }

    /**
     * @param  array<string, list<string>>  $indexesByTable
     */
    protected function dropIndexes(array $indexesByTable): void
    {
        foreach ($indexesByTable as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                foreach ($indexes as $index) {
                    if (Schema::hasIndex($tableName, $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }
    }
};
