<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use Illuminate\Support\Str;

class MigrateProductBrandsSeeder extends Seeder
{
    public function run()
    {
        // Get unique brand names from products table
        $brandStrings = Product::whereNotNull('brand')->where('brand', '!=', '')->pluck('brand')->unique();

        foreach ($brandStrings as $brandName) {
            // Find or create brand
            $brand = Brand::firstOrCreate(
                ['name->ar' => $brandName], // Assuming existing strings are Arabic or dominant name
                [
                    'name' => [
                        'ar' => $brandName,
                        'en' => $brandName, // placeholder
                    ],
                    'slug' => Str::slug($brandName) . '-' . Str::random(5),
                    'is_active' => true,
                ]
            );

            // Update products that have this brand name
            Product::where('brand', $brandName)->update([
                'brand_id' => $brand->id
            ]);
        }

        $this->command->info('Successfully migrated ' . count($brandStrings) . ' brands and linked products.');
    }
}
