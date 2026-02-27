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
        Schema::table('products', function (Blueprint $table) {
            $currentIndexes = $this->getTableIndexes('products');

            if (! in_array('products_category_id_index', $currentIndexes)) {
                $table->index('category_id');
            }
            if (! in_array('products_is_active_index', $currentIndexes)) {
                $table->index('is_active');
            }
            if (! in_array('products_price_index', $currentIndexes)) {
                $table->index('price');
            }
            if (! in_array('products_created_at_index', $currentIndexes)) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $currentIndexes = $this->getTableIndexes('products');

            if (in_array('products_category_id_index', $currentIndexes)) {
                $table->dropIndex(['category_id']);
            }
            if (in_array('products_is_active_index', $currentIndexes)) {
                $table->dropIndex(['is_active']);
            }
            if (in_array('products_price_index', $currentIndexes)) {
                $table->dropIndex(['price']);
            }
            if (in_array('products_created_at_index', $currentIndexes)) {
                $table->dropIndex(['created_at']);
            }
        });
    }

    protected function getTableIndexes(string $table): array
    {
        $conn = Schema::getConnection();
        $driver = $conn->getDriverName();

        if ($driver === 'sqlite') {
            $results = $conn->select("PRAGMA index_list($table)");
            return array_column($results, 'name');
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $results = $conn->select("SHOW INDEX FROM $table");
            return array_column($results, 'Key_name');
        }

        return [];
    }
};
