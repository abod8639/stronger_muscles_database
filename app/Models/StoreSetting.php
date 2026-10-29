<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingFactory> */
    use HasFactory;

    public const DEFAULT_ADMIN_ROLES = [Admin::ROLE_ADMIN, Admin::ROLE_SUPER_ADMIN];

    protected $fillable = [
        'store_name',
        'support_email',
        'support_phone',
        'currency',
        'delivery_fee',
        'free_delivery_threshold',
        'is_store_open',
        'order_notifications_enabled',
        'maintenance_mode',
        'allowed_admin_roles',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'free_delivery_threshold' => 'decimal:2',
            'is_store_open' => 'boolean',
            'order_notifications_enabled' => 'boolean',
            'maintenance_mode' => 'boolean',
            'allowed_admin_roles' => 'array',
        ];
    }

    /**
     * Get the store's settings row, creating it with the defaults on first use.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'allowed_admin_roles' => self::DEFAULT_ADMIN_ROLES,
        ])->refresh();
    }

    public function isAcceptingOrders(): bool
    {
        return $this->is_store_open && ! $this->maintenance_mode;
    }

    /**
     * Delivery fee for an order subtotal; free once the threshold is reached (0 disables it).
     */
    public function deliveryFeeFor(float $subtotal): float
    {
        $threshold = (float) $this->free_delivery_threshold;

        if ($threshold > 0 && $subtotal >= $threshold) {
            return 0.0;
        }

        return (float) $this->delivery_fee;
    }
}
