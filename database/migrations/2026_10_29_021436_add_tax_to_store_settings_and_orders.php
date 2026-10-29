<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a store-wide tax rate set from the dashboard and records the tax charged
 * on each order. The rate defaults to 0 so existing order totals do not change
 * until an admin sets one.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('free_delivery_threshold');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tax_amount', 10, 2)->default(0)->after('shipping_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });
    }
};
