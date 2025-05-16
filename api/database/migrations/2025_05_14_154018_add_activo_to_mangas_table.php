<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mangas', function (Blueprint $table) {
            $table->boolean('activo')
                  ->default(true)
                  ->after('en_publicacion');
        });
    }

    public function down()
    {
        Schema::table('mangas', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};

