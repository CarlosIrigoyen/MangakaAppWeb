<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Editorial extends Model
{
    use HasFactory;
    protected $table = 'editoriales'; // Asegurar el nombre correcto

    protected $fillable = ['nombre', 'pais'];


    public function editoriales(){
        return $this->belongsToMany(Editorial::class, 'tomo_editorial', 'tomo_id', 'editorial_id');
    }



}

