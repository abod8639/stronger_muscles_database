<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row table holding the store-wide settings edited from the dashboard.
 *
 * The delivery fee defaults to the 50 that checkout charged before this table
 * existed, and the free delivery threshold defaults to 0 (disabled), so order
 * totals do not change until an admin edits them.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Stronger Muscles');
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('currency', 8)->default('EGP');
            $table->decimal('delivery_fee', 10, 2)->default(50);
            $table->decimal('free_delivery_threshold', 10, 2)->default(0);
            $table->boolean('is_store_open')->default(true);
            $table->boolean('order_notifications_enabled')->default(true);
            $table->boolean('maintenance_mode')->default(false);
            $table->json('allowed_admin_roles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
