<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'product_id',
        'product_name',
        'price',
        'image_urls',
        'quantity',
        'added_at',
        'flavors',
        'size',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'added_at' => 'datetime',
            'image_urls' => 'array',
            'flavors' => 'array',
            'size' => 'array',
        ];
    }

    /**
     * Get the user that owns the cart item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: For a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: With product data
     */
    public function scopeWithProductData($query)
    {
        return $query->with('product:id,name,price,discount_price,stock_quantity,image_urls');
    }

    /**
     * Scope: Calculate total price
     */
    public function scopeWithTotalPrice($query)
    {
        return $query->selectRaw('*, (price * quantity) as total_price');
    }

    /**
     * Accessor: Get calculated subtotal
     */
    public function getTotalPriceAttribute(): float
    {
        return (float) ($this->price * $this->quantity);
    }
}
