<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageSupportToMenuItems extends Migration
{
    public function up()
    {
        // Simple "drapeau" léger : permet de savoir si l'article a une image
        // sans jamais charger l'image elle-même dans les listes d'articles.
        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedBigInteger('image_version')->nullable();
        });

        // L'image est stockée en base (base64) car le disque de Render
        // est effacé à chaque déploiement.
        Schema::create('menu_item_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id')->unique();
            $table->string('mime', 50);
            $table->longText('data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_item_images');

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('image_version');
        });
    }
}