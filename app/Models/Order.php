<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // public $incrementing = true;
    protected $fillable = [
        'id',
        'user_id',
        'order_date',
        'status',
        'payment_status',
        'payment_method',
        'address_id',
        'shipping_address_snapshot',
        'subtotal',
        'shipping_cost',
        'discount',
        'total_amount',
        'tracking_number',
        'notes',
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
            'order_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'shipping_address_snapshot' => 'array',
        ];
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope: Only completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')->orWhere('status', 'delivered');
    }

    /**
     * Scope: Pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Paid orders
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope: For a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: With items and products data
     */
    public function scopeWithItems($query)
    {
        return $query->with('orderItems:id,order_id,product_id,product_name,unit_price,quantity,subtotal,image_url');
    }

    /**
     * Scope: Recent orders first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('order_date', 'desc');
    }

    /**
     * Accessor: Get order total items count
     */
    public function getTotalItemsCountAttribute(): int
    {
        if ($this->relationLoaded('orderItems')) {
            return $this->orderItems->sum('quantity');
        }

        return (int) $this->orderItems()->sum('quantity');
    }
}
