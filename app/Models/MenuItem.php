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

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'available' => 'boolean',
    ];

    public function getHasPromotionAttribute()
    {
        return !is_null($this->promotion_discount);
    }
}