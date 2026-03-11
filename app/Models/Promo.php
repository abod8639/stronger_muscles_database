<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'button_text',
        'image_url',
        'background_color',
        'target_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'button_text' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
