<?php

namespace App\Http\Controllers;

use App\Models\MenuItemImage;

class MenuImageController extends Controller
{
    /**
     * Sert l'image d'un article. L'URL contient la version de l'image,
     * on peut donc la garder en cache un an côté navigateur.
     */
    public function show($id)
    {
        $image = MenuItemImage::where('menu_item_id', $id)->first();

        if (!$image) {
            abort(404);
        }

        return response(base64_decode($image->data), 200, [
            'Content-Type'  => $image->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}