<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('table_number');
        });

        // Backfill pour les tables clients existantes
        DB::table('users')->where('role', 'client')->whereNull('qr_token')->get()->each(function ($u) {
            DB::table('users')->where('id', $u->id)->update(['qr_token' => Str::random(48)]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};