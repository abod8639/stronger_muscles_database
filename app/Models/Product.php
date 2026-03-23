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
        'brand_id',
        'stock_quantity',
        'average_rating',
        'review_count',
        'brand',
        'serving_size',
        'servings_per_container',
        'is_active',
        'is_background_white',
        // Attributes
        'sku',
        'tags',
        'weight',
        'size',
        'flavors',
        'product_sizes',
        // Nutrition
        'nutrition_facts',
        'ingredients',
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

    /**
     * Computed: whether this product has active variants.
     */
    public function getHasVariantsAttribute(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->isNotEmpty();
        }

        return $this->variants()->exists();
    }

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
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
            'is_background_white' => 'boolean',
            'ingredients' => 'array',
            'product_sizes' => 'array',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
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

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope: Best sellers
     */
    public function scopeBestSellers($query)
    {
        return $query->where('best_seller', true);
    }

    /**
     * Scope: New arrivals
     */
    public function scopeNewArrivals($query)
    {
        return $query->where('new_arrival', true);
    }

    /**
     * Scope: Sort by price
     */
    public function scopeSortByPrice($query, $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
    }

    /**
     * Scope: Sort by rating
     */
    public function scopeSortByRating($query)
    {
        return $query->orderBy('average_rating', 'desc');
    }

    /**
     * Scope: Sort by popularity
     */
    public function scopeSortByPopularity($query)
    {
        return $query->orderBy('total_sales', 'desc')->orderBy('views_count', 'desc');
    }

    /**
     * Scope: Search by name, brand, or description
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%") // legacy
                ->orWhereHas('brand', function($bq) use ($term) {
                    $bq->where('name', 'like', "%{$term}%");
                })
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: In stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope: With category relation
     */
    public function scopeWithCategoryData($query)
    {
        return $query->with('category:id,name,slug,is_active');
    }

    /**
     * Scope: Select only necessary columns for list views
     */
    public function scopeForListView($query)
    {
        return $query->select([
            'id', 'name', 'price', 'discount_price', 'image_urls', 'average_rating',
            'review_count', 'category_id', 'stock_quantity', 'is_active', 'featured',
            'brand', 'created_at', 'updated_at', 'description', 'serving_size',
            'servings_per_container', 'flavors', 'product_sizes', 'size',
            'is_background_white', 'new_arrival', 'best_seller', 'sku', 'total_sales',
            'tags', 'weight', 'nutrition_facts',
        ]);
    }

    /**
     * Scope: Select only necessary columns for detail views
     */
    public function scopeForDetailView($query)
    {
        return $query->select([
            'id', 'name', 'price', 'discount_price', 'image_urls', 'description',
            'category_id', 'stock_quantity', 'average_rating', 'review_count',
            'brand', 'sku', 'tags', 'nutrition_facts', 'ingredients',
            'usage_instructions', 'warnings', 'created_at', 'updated_at',
            'serving_size', 'servings_per_container', 'flavors', 'product_sizes',
            'size', 'is_background_white', 'featured', 'new_arrival', 'best_seller',
            'total_sales', 'weight', 'is_active',
        ]);
    }
}
