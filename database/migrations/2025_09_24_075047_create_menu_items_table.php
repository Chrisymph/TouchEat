<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->enum('category', ['repas', 'boisson']);
            $table->boolean('available')->default(true);
            $table->decimal('promotion_discount', 5, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_items');
    }
};