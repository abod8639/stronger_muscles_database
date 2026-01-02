<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'price',
        'discount_price',
        'image_urls',
        'description',
        'category_id',
        'stock_quantity',
        'average_rating',
        'review_count',
        'brand',
        'serving_size',
        'servings_per_container',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'average_rating' => 'decimal:2',
            'review_count' => 'integer',
            'servings_per_container' => 'integer',
            'is_active' => 'boolean',
            'image_urls' => 'array',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
