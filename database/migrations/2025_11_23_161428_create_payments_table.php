<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('transaction_id');
            $table->string('network'); // mtn, moov, orange
            $table->string('phone_number');
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->unique(['transaction_id', 'network']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};