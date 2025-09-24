<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'table_number',
        'total',
        'status',
        'payment_status',
        'order_type',
        'customer_phone',
        'delivery_address',
        'estimated_time',
        'marked_ready_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'marked_ready_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}