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
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->text('image_urls')->nullable(); // Storing as JSON or string
            $table->text('description');
            $table->string('category_id')->index();
            $table->integer('stock_quantity')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->string('brand')->nullable();
            $table->string('serving_size')->nullable();
            $table->integer('servings_per_container')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->text('flavors')->nullable();
            $table->text('size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
