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
        // Basic Info
        'sku',
        'tags',
        'weight',
        'size',
        'flavors',
        // Nutrition
        'nutrition_facts',
        // Marketing
        'featured',
        'new_arrival',
        'best_seller',
        'total_sales',
        'views_count',
        // Shipping
        'shipping_weight',
        'dimensions',
        // Additional
        'ingredients',
        'usage_instructions',
        'warnings',
        'expiry_date',
        'manufacturer',
        'country_of_origin',
        // SEO
        'meta_title',
        'meta_description',
        'slug',

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
            // New casts
            'tags' => 'array',
            'flavors' => 'array',
            'nutrition_facts' => 'array',
            'dimensions' => 'array',
            'featured' => 'boolean',
            'new_arrival' => 'boolean',
            'best_seller' => 'boolean',
            'total_sales' => 'integer',
            'views_count' => 'integer',
            'shipping_weight' => 'decimal:2',
            'expiry_date' => 'date',
            'size' => 'array',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
