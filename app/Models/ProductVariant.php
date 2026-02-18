<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'sku',
        'price',
        'discount_price',
        'discount_start_date',
        'discount_end_date',
        'stock_quantity',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_start_date' => 'datetime',
        'discount_end_date' => 'datetime',
        'stock_quantity' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the current effective price.
     */
    public function getEffectivePriceAttribute()
    {
        $now = now();
        if ($this->discount_price &&
            (! $this->discount_start_date || $this->discount_start_date <= $now) &&
            (! $this->discount_end_date || $this->discount_end_date >= $now)) {
            return $this->discount_price;
        }

        return $this->price;
    }
}
