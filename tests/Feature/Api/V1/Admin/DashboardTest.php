<?php

use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

it('returns dashboard totals computed in the database', function () {
    Sanctum::actingAs($this->admin, ['*'], 'admin-api');
    Order::factory()->create(['status' => 'pending', 'total_amount' => 100]);
    Order::factory()->create(['status' => 'delivered', 'total_amount' => 250.5]);
    Order::factory()->create(['status' => 'cancelled', 'total_amount' => 999]);
    Product::factory()->create(['stock_quantity' => 50]);
    Product::factory()->create(['stock_quantity' => 5]);
    Product::factory()->create(['stock_quantity' => 0]);

    $this->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertExactJson([
            'status' => 'success',
            'data' => [
                'total_revenue' => 350.5,
                'total_orders' => 3,
                'total_users' => User::query()->count(),
                'total_products' => 3,
                'pending_orders' => 1,
                'processing_orders' => 0,
                'shipped_orders' => 0,
                'delivered_orders' => 1,
                'cancelled_orders' => 1,
                'in_stock' => 1,
                'low_stock' => 1,
                'out_of_stock' => 1,
            ],
        ]);
});

it('returns zeros for an empty store', function () {
    Sanctum::actingAs($this->admin, ['*'], 'admin-api');
    $this->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.total_revenue', 0)
        ->assertJsonPath('data.total_orders', 0)
        ->assertJsonPath('data.low_stock', 0);
});

it('returns the sidebar badge counts', function () {
    Sanctum::actingAs($this->admin, ['*'], 'admin-api');
    Order::factory()->count(2)->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'delivered']);
    Product::factory()->create(['stock_quantity' => 3]);
    Product::factory()->create(['stock_quantity' => 0]);
    Product::factory()->create(['stock_quantity' => 100]);
    foreach (['open', 'in_progress', 'resolved'] as $index => $status) {
        SupportTicket::create([
            'ticket_number' => "TKT-{$index}",
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'subject' => 'Help',
            'status' => $status,
        ]);
    }

    $this->getJson('/api/v1/admin/navigation/badges')
        ->assertSuccessful()
        ->assertExactJson([
            'status' => 'success',
            'data' => [
                'pending_orders' => 2,
                'unread_notifications' => 0,
                'open_tickets' => 2,
                'low_stock_products' => 2,
            ],
        ]);
});

it('rejects customer tokens', function (string $uri) {
    $customerToken = User::factory()->create()->createToken('app')->plainTextToken;

    $this->withToken($customerToken)->getJson($uri)->assertUnauthorized();
})->with([
    '/api/v1/admin/dashboard',
    '/api/v1/admin/navigation/badges',
]);
