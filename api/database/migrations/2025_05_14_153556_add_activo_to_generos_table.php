<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('generos', function (Blueprint $table) {
            $table->boolean('activo')
                  ->default(true)
                  ->after('nombre');
        });
    }

    public function down()
    {
        Schema::table('generos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};

