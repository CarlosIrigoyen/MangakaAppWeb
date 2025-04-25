<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(){
        Schema::create('mangas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->foreignId('autor_id')->constrained('autores'); // Relación con la tabla autores
            $table->foreignId('dibujante_id')->constrained('dibujantes'); // Relación con la tabla dibujantes
            // Nuevo campo booleano para indicar si el manga está en publicación
            $table->boolean('en_publicacion')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mangas');
    }
};
