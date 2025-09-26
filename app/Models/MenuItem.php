<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'available',
        'promotion_discount',
        'original_price',
    ];

    protected $attributes = [
        'available' => true,
        'promotion_discount' => null,
        'original_price' => null,
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'available' => 'boolean',
        'promotion_discount' => 'decimal:2',
    ];

    public function getHasPromotionAttribute()
    {
        return !is_null($this->promotion_discount);
    }
}