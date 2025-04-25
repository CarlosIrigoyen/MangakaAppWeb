<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('manga_genero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('mangas'); // Relación con la tabla mangas
            $table->foreignId('genero_id')->constrained('generos'); // Relación con la tabla generos
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manga_genero');
    }
};
