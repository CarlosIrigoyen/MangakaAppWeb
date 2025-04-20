<?php

namespace App\Http\Controllers;

use App\Models\Tomo;
use App\Models\Manga;
use App\Models\Editorial;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Database\Eloquent\Builder;


class TomoController extends Controller
{
    public function index(Request $request)
    {
        // Consulta base con relaciones
        $query = Tomo::with('manga', 'editorial', 'manga.autor');

        // Aplicar filtros en método separado
        $query = $this->applyFilters($request, $query);

        // Ordenamiento y límite
        if (! $request->filled('filter_type') && ! $request->filled('search')) {
            // Sin filtros ni búsqueda: últimos 6 tomos
            $query->orderByDesc('created_at');
        } elseif ($request->filled('filter_type')) {
            // Con filtro por tipo
            $query->join('mangas', 'mangas.id', '=', 'tomos.manga_id')
                  ->orderBy('mangas.titulo', 'asc')
                  ->orderBy('tomos.numero_tomo', 'asc')
                  ->select('tomos.*');
        } else {
            // Búsqueda sin filter_type
            $query->orderBy('numero_tomo', 'asc');
        }

        // Paginación de 6 elementos
        $tomos = $query->paginate(6)->appends($request->query());

        // Datos adicionales
        $mangas        = Manga::all();
        $editoriales   = Editorial::all();
        // Datos para creación: por combinación manga-editorial
        $nextTomos     = $this->getNextTomoData($mangas, $editoriales);
        $lowStockTomos = Tomo::where('stock', '<', 10)->with('manga')->get();
        $hasLowStock   = $lowStockTomos->isNotEmpty();

        return view('tomos.index', compact(
            'tomos', 'mangas', 'editoriales',
            'nextTomos', 'lowStockTomos', 'hasLowStock'
        ));
    }

    /**
     * Aplica filtros de consulta.
     */
    protected function applyFilters(Request $request, Builder $query): Builder
    {
        if ($idioma = $request->get('idioma')) {
            $query->where('idioma', $idioma);
        }
        if ($autor = $request->get('autor')) {
            $query->whereHas('manga.autor', function ($q) use ($autor) {
                $q->where('id', $autor);
            });
        }
        if ($mangaId = $request->get('manga_id')) {
            $query->where('manga_id', $mangaId);
        }
        if ($editorialId = $request->get('editorial_id')) {
            $query->where('editorial_id', $editorialId);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_tomo', 'like', "%{$search}%")
                  ->orWhereHas('manga', function ($q2) use ($search) {
                      $q2->where('titulo', 'like', "%{$search}%");
                  })
                  ->orWhereHas('editorial', function ($q3) use ($search) {
                      $q3->where('nombre', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    /**
     * Genera datos para el modal de creación de tomos,
     * diferenciado por combinación de manga y editorial.
     */
    protected function getNextTomoData($mangas, $editoriales): array
{
    $result = [];
    foreach ($mangas as $manga) {
        foreach ($editoriales as $editorial) {
            $last = Tomo::where('manga_id', $manga->id)
                        ->where('editorial_id', $editorial->id)
                        ->orderByDesc('numero_tomo')
                        ->first();

            if ($last) {
                $result[$manga->id][$editorial->id] = [
                    'numero'    => $last->numero_tomo + 1,
                    'fechaMin'  => Carbon::parse($last->fecha_publicacion)
                                        ->addMonth()->format('Y-m-d'),
                    'precio'    => $last->precio,
                    'formato'   => $last->formato,
                    'idioma'    => $last->idioma,
                    'readonly'  => true,
                ];
            } else {
                $result[$manga->id][$editorial->id] = [
                    'numero'    => 1,
                    'fechaMin'  => null,
                    'precio'    => null,
                    'formato'   => null,
                    'idioma'    => null,
                    'readonly'  => false,
                ];
            }
        }
    }
    return $result;
}

public function store(Request $request)
{
    // Calculamos el número siguiente automáticamente
    $nextNumero = Tomo::where('manga_id', $request->manga_id)
                      ->where('editorial_id', $request->editorial_id)
                      ->max('numero_tomo') + 1;

    // Determinamos fecha mínima solo para tomos >1
    if ($nextNumero > 1) {
        $ultimaFecha = Tomo::where('manga_id', $request->manga_id)
                           ->where('editorial_id', $request->editorial_id)
                           ->orderByDesc('numero_tomo')
                           ->value('fecha_publicacion');

        $fechaMin = Carbon::parse($ultimaFecha)
                          ->addMonth()
                          ->format('Y-m-d');
    } else {
        $fechaMin = null;
    }

    // Reglas de validación básicas
    $rules = [
        'manga_id'         => 'required|exists:mangas,id',
        'editorial_id'     => 'required|exists:editoriales,id',
        'formato'          => 'required|in:Tankōbon,Aizōban,Kanzenban,Bunkoban,Wideban',
        'idioma'           => 'required|in:Español,Inglés,Japonés',
        'precio'           => 'required|numeric',
        'stock'            => 'nullable|integer|min:0',
        'portada'          => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
    ];

    // Validación condicional de fecha_publicacion
    if ($fechaMin) {
        // Para tomos >1: fecha >= fechaMin
        $rules['fecha_publicacion'] = [
            'required',
            'date',
            'after_or_equal:'.$fechaMin,
        ];
    } else {
        // Para primer tomo: cualquier fecha válida
        $rules['fecha_publicacion'] = ['required','date'];
    }

    $validated = $request->validate($rules);

    // Subida de imagen y creación (igual que antes)
    $file = $request->file('portada');
    $manga = Manga::findOrFail($validated['manga_id']);
    $slug  = Str::slug($manga->titulo);
    $extension = $file->getClientOriginalExtension();
    $tempPath  = sys_get_temp_dir()."/portada_{$nextNumero}.{$extension}";
    $file->move(sys_get_temp_dir(), "portada_{$nextNumero}.{$extension}");

    $upload = (new UploadApi())->upload($tempPath, [
        'folder'    => "tomo_portadas/{$slug}",
        'public_id' => "portada_{$nextNumero}",
    ]);

    $validated['numero_tomo']  = $nextNumero;
    $validated['portada']      = $upload['secure_url'];
    $validated['public_id']    = $upload['public_id'];
    $validated['stock']       = $validated['stock'] ?? 0;

    Tomo::create($validated);

    @unlink($tempPath);

    return redirect()->route('tomos.index')
                     ->with('success', 'Tomo creado exitosamente.');
}

    public function destroy($id, Request $request)
    {
        $tomo = Tomo::findOrFail($id);

        // Eliminar la portada de Cloudinary
        if ($tomo->public_id) {
            (new UploadApi())->destroy($tomo->public_id);
        }

        $tomo->delete();

        $redirectTo = $request->input('redirect_to', route('tomos.index'));
        $urlComponents = parse_url($redirectTo);
        $queryParams   = [];
        if (isset($urlComponents['query'])) {
            parse_str($urlComponents['query'], $queryParams);
        }

        // Reconstruir la consulta para conservar filtros y paginación
        $query = Tomo::with('manga', 'editorial', 'manga.autor');
        if (isset($queryParams['filter_type'])) {
            $filterType = $queryParams['filter_type'];
            if ($filterType == 'idioma' && !empty($queryParams['idioma'])) {
                $query->where('idioma', $queryParams['idioma']);
            } elseif ($filterType == 'autor' && !empty($queryParams['autor'])) {
                $autor = $queryParams['autor'];
                $query->whereHas('manga.autor', function ($q) use ($autor) {
                    $q->where('id', $autor);
                });
            } elseif ($filterType == 'manga' && !empty($queryParams['manga_id'])) {
                $query->where('manga_id', $queryParams['manga_id']);
            } elseif ($filterType == 'editorial' && !empty($queryParams['editorial_id'])) {
                $query->where('editorial_id', $queryParams['editorial_id']);
            }
        }

        if (!empty($queryParams['search'])) {
            $search = $queryParams['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_tomo', 'like', "%{$search}%")
                  ->orWhereHas('manga', function ($q2) use ($search) {
                      $q2->where('titulo', 'like', "%{$search}%");
                  })
                  ->orWhereHas('editorial', function ($q3) use ($search) {
                      $q3->where('nombre', 'like', "%{$search}%");
                  });
            });
        }

        $tomos     = $query->paginate(6)->appends($queryParams);
        $currentPage = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $lastPage    = $tomos->lastPage();

        if ($currentPage > $lastPage) {
            $queryParams['page'] = $lastPage;
            $redirectTo = route('tomos.index', $queryParams);
        }

        return redirect($redirectTo)->with('success', 'Tomo eliminado exitosamente.');
    }

    public function edit($id)
    {
        $tomo = Tomo::with('manga', 'editorial')->findOrFail($id);
        $mangas = Manga::all();
        $editoriales = Editorial::all();

        return response()->json([
            'tomo'       => $tomo,
            'mangas'     => $mangas,
            'editoriales'=> $editoriales,
        ]);
    }

    public function update(Request $request, $id)
    {
        $tomo = Tomo::findOrFail($id);

        $rules = [
            'manga_id'          => 'required|exists:mangas,id',
            'editorial_id'      => 'required|exists:editoriales,id',
            'formato'           => 'required|in:Tankōbon,Aizōban,Kanzenban,Bunkoban,Wideban',
            'idioma'            => 'required|in:Español,Inglés,Japonés',
            'precio'            => 'required|numeric',
            'fecha_publicacion' => 'required|date|before:' . date('Y-m-d'),
            'stock'             => 'sometimes|numeric|min:0',
        ];

        if ($request->hasFile('portada')) {
            $rules['portada'] = 'image|max:5120'; // máximo 5MB
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('portada')) {
            // Eliminar imagen anterior de Cloudinary
            if ($tomo->public_id) {
                (new UploadApi())->destroy($tomo->public_id);
            }

            $file = $request->file('portada');
            $extension = $file->getClientOriginalExtension();
            $tempPath  = sys_get_temp_dir() . "/portada_{$tomo->numero_tomo}.{$extension}";
            $file->move(sys_get_temp_dir(), "portada_{$tomo->numero_tomo}.{$extension}");

            $manga = Manga::findOrFail($validated['manga_id']);
            $slug  = Str::slug($manga->titulo);

            // Subir nueva portada a Cloudinary
            $upload = (new UploadApi())->upload($tempPath, [
                'folder'    => "tomo_portadas/{$slug}",
                'public_id' => "portada_{$tomo->numero_tomo}",
            ]);

            // Asignar URL pública y public_id
            $validated['portada']   = $upload['secure_url'];
            $validated['public_id'] = $upload['public_id'];

            @unlink($tempPath);
        }

        $tomo->update($validated);

        $redirectTo = $request->input('redirect_to', route('tomos.index'));
        return redirect($redirectTo)->with('success', 'Tomo actualizado correctamente.');
    }
    public function updateMultipleStock(Request $request)
{
    // Validar que se envíe un array de tomos con el stock y que cada stock sea un entero mínimo 1.
    $request->validate([
        'tomos' => 'required|array',
        'tomos.*.id' => 'required|exists:tomos,id',
        'tomos.*.stock' => 'required|integer|min:1',
    ]);

    // Recorrer cada entrada y actualizar el stock correspondiente.
    foreach ($request->tomos as $tomoData) {
        $tomo = Tomo::findOrFail($tomoData['id']);
        $tomo->update([
            'stock' => $tomoData['stock'],
        ]);
    }

    // Redireccionar al listado con mensaje de éxito.
    return redirect()->route('tomos.index')->with('success', 'Stocks actualizados correctamente.');
}
// TomoController.php (o el controlador que maneje la ruta pública)
public function indexPublic(Request $request)
{
    // Iniciar la consulta con las relaciones necesarias, incluyendo géneros
    $query = Tomo::with('manga', 'editorial', 'manga.autor', 'manga.generos');

    // Filtros enviados como arrays
    if ($request->has('authors')) {
        $authors = explode(',', $request->get('authors'));
        $query->whereHas('manga.autor', function ($q) use ($authors) {
            $q->whereIn('id', $authors);
        });
    }
    if ($request->has('languages')) {
        $languages = explode(',', $request->get('languages'));
        $query->whereIn('idioma', $languages);
    }
    if ($request->has('mangas')) {
        $mangas = explode(',', $request->get('mangas'));
        $query->whereIn('manga_id', $mangas);
    }
    if ($request->has('editorials')) {
        $editorials = explode(',', $request->get('editorials'));
        $query->whereIn('editorial_id', $editorials);
    }

    if ($filterType = $request->get('filter_type')) {
        if ($filterType == 'idioma' && $idioma = $request->get('idioma')) {
            $query->where('idioma', $idioma);
        } elseif ($filterType == 'autor' && $autor = $request->get('autor')) {
            $query->whereHas('manga.autor', function ($q) use ($autor) {
                $q->where('id', $autor);
            });
        } elseif ($filterType == 'manga' && $mangaId = $request->get('manga_id')) {
            $query->where('manga_id', $mangaId);
        } elseif ($filterType == 'editorial' && $editorialId = $request->get('editorial_id')) {
            $query->where('editorial_id', $editorialId);
        }
    }

    // Filtro de búsqueda general
    if ($search = $request->get('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('numero_tomo', 'like', "%$search%")
              ->orWhereHas('manga', function ($q2) use ($search) {
                  $q2->where('titulo', 'like', "%$search%");
              })
              ->orWhereHas('editorial', function ($q3) use ($search) {
                  $q3->where('nombre', 'like', "%$search%");
              });
        });
    }

    // Filtro por rango de precio (aplica solo si se activa con el botón)
    if (
        $request->has('applyPriceFilter') &&
        $request->get('applyPriceFilter') == 1 &&
        $request->has('minPrice') &&
        $request->has('maxPrice')
    ) {
        // Convertir los valores a float para comparar numéricamente
        $minPrice = (float)$request->get('minPrice');
        $maxPrice = (float)$request->get('maxPrice');
        $query->whereBetween('precio', [$minPrice, $maxPrice]);
    }

    // Ordenar primero por título del manga (alfabético) y luego por número de tomo
    $query->orderByRaw("(select titulo from mangas where mangas.id = tomos.manga_id) asc");
    $query->orderBy('numero_tomo', 'asc');

    // Paginación de 8 ítems por página y conservar parámetros en la URL
    $tomos = $query->paginate(8)->appends($request->query());

    // Retorna la estructura paginada. Si no hay resultados, data será un array vacío.
    return response()->json($tomos);
}



}
