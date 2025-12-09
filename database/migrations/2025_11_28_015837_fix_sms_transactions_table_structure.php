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
        // Ajouter uniquement les index sans modifier les colonnes
        Schema::table('sms_transactions', function (Blueprint $table) {
            // Index composite pour les recherches fréquentes
            $table->index(['transaction_id', 'network', 'status'], 'sms_transactions_main_search');
            
            // Index pour le numéro d'expéditeur
            $table->index(['sender_number'], 'sms_transactions_sender_index');
            
            // Index pour la date de réception
            $table->index(['sms_received_at'], 'sms_transactions_date_index');
            
            // Index pour le montant
            $table->index(['amount'], 'sms_transactions_amount_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex('sms_transactions_main_search');
            $table->dropIndex('sms_transactions_sender_index');
            $table->dropIndex('sms_transactions_date_index');
            $table->dropIndex('sms_transactions_amount_index');
        });
    }
};