<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // daily, weekly, monthly, custom
            $table->date('start_date');
            $table->date('end_date');
            $table->json('data')->nullable(); // Stockage des données statistiques en JSON
            $table->text('description')->nullable();
            $table->boolean('is_generated')->default(false);
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['type', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};