<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTomosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tomos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('mangas')->onDelete('cascade');
            $table->foreignId('editorial_id')->constrained('editoriales')->onDelete('cascade');
            $table->unsignedInteger('numero_tomo');
            $table->enum('formato', ['Tankōbon', 'Aizōban', 'Kanzenban', 'Bunkoban', 'Wideban']);
            $table->enum('idioma', ['Español', 'Inglés', 'Japonés']);
            $table->decimal('precio', 8, 2);
            $table->date('fecha_publicacion');
            $table->string('portada'); // Guarda la ruta de la imagen
            $table->unsignedInteger('stock')->default(0); // Nuevo atributo stock
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tomos');
    }
}
