<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use App\Models\Manga;
use App\Models\Editorial;
use App\Models\Tomo;
use Illuminate\Http\JsonResponse;

class FiltroController extends Controller
{
    public function getFilters(): JsonResponse
    {
        // === AUTORES CON AL MENOS UN MANGA ACTIVO Y AL MENOS UN TOMO ACTIVO ===
        $authors = Autor::select('id', 'nombre', 'apellido')
            ->where('activo', true)
            ->whereHas('mangas', function ($q) {
                $q->where('activo', true)
                  ->whereHas('tomos', function ($q) {
                      $q->where('activo', true);
                  });
            })
            ->get();

        // === MANGAS CON AUTOR ACTIVO Y AL MENOS UN TOMO ACTIVO ===
        $mangas = Manga::select('id', 'titulo')
            ->where('activo', true)
            ->whereHas('autor', function ($q) {
                $q->where('activo', true);
            })
            ->whereHas('tomos', function ($q) {
                $q->where('activo', true);
            })
            ->get();

        // === EDITORIALES CON AL MENOS UN TOMO ACTIVO ===
        $editorials = Editorial::select('id', 'nombre')
            ->where('activo', true)
            ->whereHas('tomos', function ($q) {
                $q->where('activo', true);
            })
            ->get();

        // === IDIOMAS Y RANGO DE PRECIOS ===
        // === IDIOMAS DE TOMOS ACTIVOS (MODIFICADO) ===
        $languages = Tomo::where('activo', true)
            ->distinct()
            ->pluck('idioma')
            ->filter() // Elimina valores null o vacíos
            ->values()
            ->toArray();

        // Si no hay idiomas, usar array vacío en lugar de los fijos
        if (empty($languages)) {
            $languages = [];
        }

        $minPrice = Tomo::where('activo', true)->min('precio');
        $maxPrice = Tomo::where('activo', true)->max('precio');

        return response()->json(compact('authors', 'languages', 'mangas', 'editorials', 'minPrice', 'maxPrice'));
    }
}
