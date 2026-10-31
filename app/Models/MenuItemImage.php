<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemImage extends Model
{
    protected $fillable = [
        'menu_item_id',
        'mime',
        'data',
    ];
}