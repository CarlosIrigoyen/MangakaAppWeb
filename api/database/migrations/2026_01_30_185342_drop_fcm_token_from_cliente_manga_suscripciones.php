<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('cliente_manga_suscripciones')) {
            Schema::table('cliente_manga_suscripciones', function (Blueprint $table) {
                if (Schema::hasColumn('cliente_manga_suscripciones', 'fcm_token')) {
                    $table->dropUnique(['cliente_id', 'manga_id', 'fcm_token']); // intenta eliminar índice compuesto si existe
                    $table->dropColumn('fcm_token');
                }

                // Aseguramos un índice único por cliente_id + manga_id
                try {
                    $table->unique(['cliente_id', 'manga_id'], 'cliente_manga_unique');
                } catch (\Throwable $e) {
                    // si ya existe, ignorar
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('cliente_manga_suscripciones')) {
            Schema::table('cliente_manga_suscripciones', function (Blueprint $table) {
                if (!Schema::hasColumn('cliente_manga_suscripciones', 'fcm_token')) {
                    $table->string('fcm_token')->nullable()->after('manga_id');
                }
                try {
                    $table->dropUnique('cliente_manga_unique');
                } catch (\Throwable $e) {}
            });
        }
    }
};
