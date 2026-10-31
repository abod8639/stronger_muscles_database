<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can create a product', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $category = Category::factory()->create();

    $productData = [
        'id' => 'prod-123',
        'name' => ['ar' => 'بروتين جديد', 'en' => 'New Protein'],
        'price' => 50.00,
        'description' => ['ar' => 'وصف البروتين', 'en' => 'Great protein'],
        'category_id' => $category->id,
        'stock_quantity' => 100,
    ];

    $response = $this->postJson('/api/v1/admin/products', $productData);

    $response->assertStatus(201)
        ->assertJsonFragment(['en' => 'New Protein']);

    $this->assertDatabaseHas('products', ['id' => 'prod-123']);
});

test('admin can update a product', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $product = Product::factory()->create();

    $updateData = [
        'name' => ['ar' => 'اسم معدل', 'en' => 'Updated Name'],
        'price' => 60.00,
    ];

    $response = $this->putJson("/api/v1/admin/products/{$product->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonFragment(['en' => 'Updated Name']);

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('admin can delete a product', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $product = Product::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/products/{$product->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('non-admin cannot manage products', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $this->getJson('/api/v1/admin/products')->assertStatus(401);
});

test('admin can create product with nutrition facts, tags, sku, variants, new arrival and best seller', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $category = Category::factory()->create();

    $productData = [
        'id' => 'prod-supp-999',
        'name' => ['ar' => 'واي جولد ستاندرد', 'en' => 'Gold Standard Whey'],
        'price' => 85.00,
        'discount_price' => 75.00,
        'description' => ['ar' => 'بروتين نقي لبناء العضلات', 'en' => 'Pure protein for muscle growth'],
        'category_id' => $category->id,
        'stock_quantity' => 150,
        'sku' => 'OPT-WHEY-GOLD',
        'tags' => ['whey', 'protein', 'keto'],
        'new_arrival' => true,
        'best_seller' => true,
        'is_active' => true,
        'is_background_white' => true,
        'nutrition_facts' => [
            'calories' => '120 kcal',
            'protein' => '24g',
            'carbs' => '3g',
            'fat' => '1.5g',
            'allergens' => ['Milk', 'Soy'],
        ],
        'product_variants' => [
            [
                'id' => 'var-1kg-choc',
                'sku' => 'OPT-WHEY-1KG-CHOC',
                'price' => 45.00,
                'discount_price' => null,
                'effective_price' => 45.00,
                'stock_quantity' => 50,
                'attributes' => ['size' => '1kg', 'flavor' => 'Chocolate'],
                'is_active' => true,
            ],
            [
                'id' => 'var-2kg-van',
                'sku' => 'OPT-WHEY-2KG-VAN',
                'price' => 85.00,
                'discount_price' => 75.00,
                'effective_price' => 75.00,
                'stock_quantity' => 100,
                'attributes' => ['size' => '2kg', 'flavor' => 'Vanilla'],
                'is_active' => true,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/admin/products', $productData);

    $response->assertStatus(201)
        ->assertJsonPath('data.sku', 'OPT-WHEY-GOLD')
        ->assertJsonPath('data.tags', ['whey', 'protein', 'keto'])
        ->assertJsonPath('data.new_arrival', true)
        ->assertJsonPath('data.best_seller', true)
        ->assertJsonPath('data.nutrition_facts.protein', '24g')
        ->assertJsonPath('data.has_variants', true);

    $this->assertDatabaseHas('products', [
        'id' => 'prod-supp-999',
        'sku' => 'OPT-WHEY-GOLD',
        'new_arrival' => 1,
        'best_seller' => 1,
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => 'var-1kg-choc',
        'sku' => 'OPT-WHEY-1KG-CHOC',
        'stock_quantity' => 50,
    ]);
});
