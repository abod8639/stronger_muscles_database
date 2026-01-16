<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'full_name',
        'phone',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'double',
            'longitude' => 'double',
            'user_id' => 'integer',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
