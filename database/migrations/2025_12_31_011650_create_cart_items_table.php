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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->index();
            $table->string('product_id');
            $table->string('product_name');
            $table->decimal('price', 10, 2);
            $table->text('image_urls')->nullable();
            $table->integer('quantity')->default(1);
            $table->dateTime('added_at')->useCurrent();
            $table->text('flavors')->nullable();
            $table->text('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
