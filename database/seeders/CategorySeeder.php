<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => 'cat-protein',
                'name' => 'البروتينات',
                'description' => 'بروتينات لبناء العضلات والتعافي السريع بعد التمرين',
                'image_url' => 'https://picsum.photos/seed/protein/300/200',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'id' => 'cat-amino',
                'name' => 'الأحماض الأمينية',
                'description' => 'أحماض أمينية أساسية لتعزيز الأداء الرياضي والتعافي',
                'image_url' => 'https://picsum.photos/seed/amino/300/200',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'id' => 'cat-vitamins',
                'name' => 'الفيتامينات والمعادن',
                'description' => 'فيتامينات ومعادن أساسية لدعم الصحة العامة والمناعة',
                'image_url' => 'https://picsum.photos/seed/vitamins/300/200',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'id' => 'cat-preworkout',
                'name' => 'محفزات الطاقة',
                'description' => 'منتجات ما قبل التمرين لتعزيز الطاقة والتركيز',
                'image_url' => 'https://picsum.photos/seed/preworkout/300/200',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'id' => 'cat-recovery',
                'name' => 'التعافي',
                'description' => 'منتجات تساعد على التعافي والاستشفاء بعد التمرين',
                'image_url' => 'https://picsum.photos/seed/recovery/300/200',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'id' => 'cat-fatburner',
                'name' => 'حرق الدهون',
                'description' => 'منتجات لدعم فقدان الوزن وحرق الدهون',
                'image_url' => 'https://picsum.photos/seed/fatburner/300/200',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'id' => 'cat-health',
                'name' => 'الصحة العامة',
                'description' => 'مكملات غذائية للصحة والعافية العامة',
                'image_url' => 'https://picsum.photos/seed/health/300/200',
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
