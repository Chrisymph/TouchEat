<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Premièrement, rendre transaction_id nullable si ce n'est pas déjà le cas
        DB::statement('ALTER TABLE payments MODIFY transaction_id VARCHAR(255) NULL');
        
        // Ensuite, mettre à jour les valeurs NULL existantes
        DB::table('payments')
            ->whereNull('transaction_id')
            ->update(['transaction_id' => 'pending_' . DB::raw('UNIX_TIMESTAMP()')]);
            
        // Ajouter une valeur par défaut pour status si nécessaire
        DB::statement('ALTER TABLE payments MODIFY status VARCHAR(255) DEFAULT "en_attente"');
    }

    public function down()
    {
        // Pour rollback, remettre les valeurs comme avant
        DB::statement('ALTER TABLE payments MODIFY transaction_id VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE payments MODIFY status VARCHAR(255) DEFAULT NULL');
    }
};