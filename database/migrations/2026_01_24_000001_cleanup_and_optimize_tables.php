<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // 1. Fix user_id type in orders table
    // First check if table exists and if we need to change it
    if (Schema::hasTable('orders')) {
      Schema::table('orders', function (Blueprint $table) {
        // Use change() instead of DB::statement for SQLite compatibility
        $table->unsignedBigInteger('user_id')->change();

        // Add indexes for performance
        if (!Schema::hasIndex('orders', ['status'])) {
          $table->index('status');
        }
        if (!Schema::hasIndex('orders', ['payment_status'])) {
          $table->index('payment_status');
        }
        if (!Schema::hasIndex('orders', ['user_id'])) {
          $table->index('user_id');
        }
      });
    }

    // 2. Add performance indexes to products table
    if (Schema::hasTable('products')) {
      Schema::table('products', function (Blueprint $table) {
        if (!Schema::hasIndex('products', ['is_active'])) {
          $table->index('is_active');
        }
        if (!Schema::hasIndex('products', ['featured'])) {
          $table->index('featured');
        }
        if (!Schema::hasIndex('products', ['category_id'])) {
          $table->index('category_id');
        }

        // Clean up redundant flavor column (if exists from earlier messy migrations)
        if (Schema::hasColumn('products', 'flavor') && Schema::hasColumn('products', 'flavors')) {
          $table->dropColumn('flavor');
        }
      });
    }

    // 3. Add performance index to order_items
    if (Schema::hasTable('order_items')) {
      Schema::table('order_items', function (Blueprint $table) {
        if (!Schema::hasIndex('order_items', ['product_id'])) {
          $table->index('product_id');
        }
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropIndex(['status']);
      $table->dropIndex(['payment_status']);
      $table->dropIndex(['user_id']);
    });

    Schema::table('products', function (Blueprint $table) {
      $table->dropIndex(['is_active']);
      $table->dropIndex(['featured']);
      $table->dropIndex(['category_id']);
    });

    Schema::table('order_items', function (Blueprint $table) {
      $table->dropIndex(['product_id']);
    });
  }
};
