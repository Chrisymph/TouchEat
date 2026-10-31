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

    // image_url est ajouté automatiquement à chaque article (@json, réponses AJAX...)
    protected $appends = ['image_url'];

    public function getHasPromotionAttribute()
    {
        return !is_null($this->promotion_discount);
    }

    /**
     * URL relative de l'image (null si l'article n'a pas d'image).
     * Le paramètre "v" change à chaque nouvelle image : le navigateur
     * garde l'ancienne en cache jusqu'à ce qu'elle soit remplacée.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_version) {
            return null;
        }

        return route('menu.image', ['id' => $this->id, 'v' => $this->image_version], false);
    }
}