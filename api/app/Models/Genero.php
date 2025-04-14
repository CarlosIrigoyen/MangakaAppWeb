<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genero extends Model
{
    use HasFactory;
    protected $table = 'generos';
    protected $fillable = ['nombre'];

    // Relación muchos a muchos con mangas
    public function mangas()
    {
        return $this->belongsToMany(Manga::class);
    }
}

