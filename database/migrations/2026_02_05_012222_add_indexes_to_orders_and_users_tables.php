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
        Schema::table('orders', function (Blueprint $table) {
            $currentIndexes = $this->getTableIndexes('orders');

            if (! in_array('orders_user_id_index', $currentIndexes)) {
                $table->index('user_id');
            }
            if (! in_array('orders_status_index', $currentIndexes)) {
                $table->index('status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $currentIndexes = $this->getTableIndexes('users');

            if (! in_array('users_email_index', $currentIndexes)) {
                $table->index('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
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
