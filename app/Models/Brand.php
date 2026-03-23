<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (!$brand->slug) {
                $nameEn = $brand->name['en'] ?? $brand->name['ar'] ?? 'brand';
                $brand->slug = Str::slug($nameEn) . '-' . Str::random(5);
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Helper to get localized name
     */
    public function getDisplayNameAttribute()
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['ar'] ?? $this->name['en'] ?? '';
    }
}
