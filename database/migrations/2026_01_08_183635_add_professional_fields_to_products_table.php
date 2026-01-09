<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Basic Info
            $table->string('sku')->nullable()->unique()->after('id');
            $table->json('tags')->nullable()->after('description');

            $table->string('weight')->nullable()->after('tags');
            $table->string('size')->nullable()->after('weight');

            // Nutrition Facts
            $table->json('nutrition_facts')->nullable()->after('size');

            // Marketing
            $table->boolean('featured')->default(false)->after('is_active');
            $table->boolean('new_arrival')->default(false)->after('featured');
            $table->boolean('best_seller')->default(false)->after('new_arrival');
            $table->integer('total_sales')->default(0)->after('best_seller');
            $table->integer('views_count')->default(0)->after('total_sales');

            // Shipping
            $table->decimal('shipping_weight', 8, 2)->nullable()->after('views_count');
            $table->json('dimensions')->nullable()->after('shipping_weight');

            // Additional Info
            $table->text('ingredients')->nullable()->after('dimensions');
            $table->text('usage_instructions')->nullable()->after('ingredients');
            $table->text('warnings')->nullable()->after('usage_instructions');
            $table->date('expiry_date')->nullable()->after('warnings');
            $table->string('manufacturer')->nullable()->after('expiry_date');
            $table->string('country_of_origin')->nullable()->after('manufacturer');

            // SEO
            $table->string('meta_title')->nullable()->after('country_of_origin');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('slug')->nullable()->unique()->after('meta_description');

            //flavor
            $table->json('flavor')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'tags',
                'weight',
                'size',
                'nutrition_facts',
                'featured',
                'new_arrival',
                'best_seller',
                'total_sales',
                'views_count',
                'shipping_weight',
                'dimensions',
                'ingredients',
                'usage_instructions',
                'warnings',
                'expiry_date',
                'manufacturer',
                'country_of_origin',
                'meta_title',
                'meta_description',
                'slug',
                'flavor',
            ]);
        });
    }
};
