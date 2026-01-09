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
            if (!Schema::hasColumn('products', 'flavors')) {
                $table->json('flavors')->nullable()->after('is_active');
            }

            // Drop naming mismatch column if it exists and we haven't already
            if (Schema::hasColumn('products', 'flavor')) {
                $table->dropColumn('flavor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'flavors')) {
                $table->dropColumn('flavors');
            }
        });
    }
};
