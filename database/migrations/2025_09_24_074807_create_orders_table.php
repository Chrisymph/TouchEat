<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('table_number');
            $table->decimal('total', 10, 2);
            $table->enum('status', ['commandé', 'en_cours', 'prêt', 'livré', 'terminé'])->default('commandé');
            $table->enum('payment_status', ['en_attente', 'payé', 'échoué'])->default('en_attente');
            $table->enum('order_type', ['sur_place', 'livraison'])->default('sur_place');
            $table->string('customer_phone')->nullable();
            $table->text('delivery_address')->nullable();
            $table->integer('estimated_time')->nullable(); // en minutes
            $table->timestamp('marked_ready_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};