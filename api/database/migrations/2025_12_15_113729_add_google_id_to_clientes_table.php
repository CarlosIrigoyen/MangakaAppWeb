<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('google_id')
                  ->nullable()
                  ->unique()
                  ->after('password')
                  ->comment('ID único de Google para login social');
        });
    }

    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
