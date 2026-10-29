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
        'tax_rate',
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
            'tax_rate' => 'decimal:2',
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

    /**
     * Tax on the products subtotal (delivery is not taxed), rounded to 2 decimals.
     */
    public function taxFor(float $subtotal): float
    {
        return round($subtotal * (float) $this->tax_rate / 100, 2);
    }

    /**
     * Delivery, tax and grand total for an order subtotal.
     *
     * @return array{subtotal: float, shipping_cost: float, tax_amount: float, discount: float, total_amount: float}
     */
    public function totalsFor(float $subtotal, float $discount = 0): array
    {
        $shippingCost = $this->deliveryFeeFor($subtotal);
        $taxAmount = $this->taxFor($subtotal);

        return [
            'subtotal' => round($subtotal, 2),
            'shipping_cost' => $shippingCost,
            'tax_amount' => $taxAmount,
            'discount' => $discount,
            'total_amount' => round(max(0, $subtotal + $shippingCost + $taxAmount - $discount), 2),
        ];
    }
}
