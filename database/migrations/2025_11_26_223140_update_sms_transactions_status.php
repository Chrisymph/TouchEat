<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Modifier l'enum pour ajouter 'received'
        DB::statement("ALTER TABLE sms_transactions MODIFY COLUMN status ENUM('received', 'pending', 'used', 'expired') DEFAULT 'received'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE sms_transactions MODIFY COLUMN status ENUM('pending', 'used', 'expired') DEFAULT 'pending'");
    }
};