<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns reference column types with the keys they point to, removes orphaned
 * rows that would violate the new constraints, and adds the missing foreign keys.
 *
 * Order items keep their snapshot (name, price, image) when a product is deleted,
 * so their product reference is nulled instead of cascading. Orders are financial
 * records, so deleting a user that still has orders is restricted.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->relaxColumnsBeforeCleanup();
        $this->removeOrphanedReferences();
        $this->alignColumnTypes();
        $this->addForeignKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_address_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('default_address_id')->nullable()->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('user_id')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_id')->nullable()->change();
            $table->string('product_name')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['address_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('address_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }

    /**
     * Make reference columns nullable so dangling values can be cleared before retyping.
     */
    protected function relaxColumnsBeforeCleanup(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('address_id')->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_id')->nullable()->change();
        });
    }

    /**
     * Delete or detach rows that reference records which no longer exist.
     */
    protected function removeOrphanedReferences(): void
    {
        DB::table('order_items')
            ->whereNotExists(fn (Builder $query) => $query->from('orders')->whereColumn('orders.id', 'order_items.order_id'))
            ->delete();

        DB::table('order_items')
            ->whereNotNull('product_id')
            ->whereNotExists(fn (Builder $query) => $query->from('products')->whereColumn('products.id', 'order_items.product_id'))
            ->update(['product_id' => null]);

        DB::table('cart_items')
            ->where(fn (Builder $query) => $query
                ->whereNotExists(fn (Builder $query) => $query->from('users')->whereColumn('users.id', 'cart_items.user_id'))
                ->orWhereNotExists(fn (Builder $query) => $query->from('products')->whereColumn('products.id', 'cart_items.product_id')))
            ->delete();

        DB::table('orders')
            ->whereNotNull('address_id')
            ->whereNotExists(fn (Builder $query) => $query->from('addresses')->whereColumn('addresses.id', 'orders.address_id'))
            ->update(['address_id' => null]);

        DB::table('users')
            ->whereNotNull('default_address_id')
            ->whereNotExists(fn (Builder $query) => $query->from('addresses')->whereColumn('addresses.id', 'users.default_address_id'))
            ->update(['default_address_id' => null]);

        DB::table('support_tickets')
            ->whereNotNull('order_id')
            ->whereNotExists(fn (Builder $query) => $query->from('orders')->whereColumn('orders.id', 'support_tickets.order_id'))
            ->update(['order_id' => null]);
    }

    /**
     * Retype reference columns to match their target keys and widen JSON name columns.
     */
    protected function alignColumnTypes(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('address_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('default_address_id')->nullable()->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        if ($this->hasForeignKey('support_tickets', 'order_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('order_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->text('product_name')->change();
        });
    }

    /**
     * Add the foreign keys that the original tables were created without.
     */
    protected function addForeignKeys(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('address_id')->references('id')->on('addresses')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('default_address_id')->references('id')->on('addresses')->nullOnDelete();
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    protected function hasForeignKey(string $table, string $column): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]);
    }
};
