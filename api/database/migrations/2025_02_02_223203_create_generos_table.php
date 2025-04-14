<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenerosTable extends Migration
{
    public function up()
    {
        Schema::create('generos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // El nombre del género
            $table->timestamps(); // Añade las columnas created_at y updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('generos');
    }
}

