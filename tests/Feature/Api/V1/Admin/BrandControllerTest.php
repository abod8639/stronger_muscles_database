<?php

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');
});

it('lists brands with their product counts', function () {
    $brand = Brand::factory()->create();
    Product::factory()->count(2)->create(['brand_id' => $brand->id]);

    $this->getJson('/api/v1/admin/brands')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $brand->id)
        ->assertJsonPath('data.0.productsCount', 2);
});

it('lists brands with a fixed number of queries', function () {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/v1/admin/brands')->assertSuccessful();

        return count(DB::getQueryLog());
    };

    Brand::factory()->create();
    $queriesForOneBrand = $countQueries();

    Brand::factory()->count(5)->create();
    $queriesForSixBrands = $countQueries();

    expect($queriesForSixBrands)->toBe($queriesForOneBrand);
});
