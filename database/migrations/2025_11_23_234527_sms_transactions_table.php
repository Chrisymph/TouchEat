<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sms_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->string('sender_number');
            $table->string('receiver_number');
            $table->decimal('amount', 10, 2);
            $table->enum('network', ['mtn', 'moov', 'orange']);
            $table->text('message');
            $table->timestamp('sms_received_at');
            $table->enum('status', ['pending', 'used', 'expired'])->default('pending');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'network']);
            $table->index(['status', 'sms_received_at']);
            $table->index('sender_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_transactions');
    }
};