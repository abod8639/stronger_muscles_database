<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo_url',
        'phone_number',
        'default_address_id',
        'preferred_language',
        'notifications_enabled',
        'last_login',
        'role',
        'is_active',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
            'notifications_enabled' => 'boolean',
        ];
    }

    /**
     * Get the orders for the user.
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the cart items for the user.
     */
    public function cartItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the products the user saved to their wishlist.
     */
    public function wishlistItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the addresses for the user.
     */
    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the reviews written by the user.
     */
    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get support tickets created by the user.
     */
    public function supportTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Accessor for total spent.
     */
    public function getTotalSpentAttribute(): float
    {
        if (array_key_exists('total_spent', $this->attributes)) {
            return (float) $this->attributes['total_spent'];
        }

        return (float) $this->orders()->where('status', 'delivered')->sum('total_amount');
    }

    /**
     * Accessor for orders count.
     */
    public function getOrdersCountAttribute(): int
    {
        if (array_key_exists('orders_count', $this->attributes)) {
            return (int) $this->attributes['orders_count'];
        }

        return (int) $this->orders()->count();
    }
}
