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



class TomoController extends Controller
{
    public function index(Request $request)
    {
        // Obtener parámetros de filtrado
        $filterType = $request->get('filter_type'); // 'idioma', 'autor', 'manga' o 'editorial'
        $search     = $request->get('search');

        // Construir la consulta principal (con relaciones)
        $query = Tomo::with('manga', 'editorial', 'manga.autor');

        // Aplicar filtros según el criterio
        if ($filterType) {
            if ($filterType == 'idioma') {
                $idioma = $request->get('idioma');
                if ($idioma) {
                    $query->where('idioma', $idioma);
                }
            } elseif ($filterType == 'autor') {
                // Se espera que el select de autor envíe el id del autor
                $autor = $request->get('autor');
                if ($autor) {
                    $query->whereHas('manga.autor', function ($q) use ($autor) {
                        $q->where('id', $autor);
                    });
                }
            } elseif ($filterType == 'manga') {
                $mangaId = $request->get('manga_id');
                if ($mangaId) {
                    $query->where('manga_id', $mangaId);
                }
            } elseif ($filterType == 'editorial') {
                $editorialId = $request->get('editorial_id');
                if ($editorialId) {
                    $query->where('editorial_id', $editorialId);
                }
            }
        }

        if ($search) {
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

        /**
         * Ordenamiento:
         * Si se aplica un filtro basado en algún criterio (por ejemplo, 'editorial', 'idioma', 'autor' o 'manga'),
         * se ordena primero por el título del manga (alfabéticamente) y luego por el número de tomo.
         * En caso contrario, se ordena únicamente por el número de tomo.
         */
        if ($filterType && in_array($filterType, ['idioma','autor','manga','editorial'])) {
            // Realizamos un join con la tabla de mangas para ordenar por el título
            $query->join('mangas', 'mangas.id', '=', 'tomos.manga_id')
                  ->orderBy('mangas.titulo', 'asc')
                  ->orderBy('tomos.numero_tomo', 'asc')
                  ->select('tomos.*'); // Evitamos conflictos al seleccionar sólo columnas de tomos
        } else {
            $query->orderBy('numero_tomo', 'asc');
        }

        // Paginar 6 tomos por página y conservar los parámetros en la URL
        $tomos = $query->paginate(6)->appends($request->query());

        // Consultar todas las opciones para los selects (para filtros y para el modal de creación)
        $mangas = Manga::all();
        $editoriales = Editorial::all();

        // Calcular, para cada manga, el próximo número de tomo y la fecha mínima (último tomo + 1 mes)
        $nextTomos = [];
        foreach ($mangas as $manga) {
            $lastTomo = Tomo::where('manga_id', $manga->id)
                            ->orderBy('numero_tomo', 'desc')
                            ->first();
            if ($lastTomo) {
                $nextTomos[$manga->id] = [
                    'numero_tomo' => $lastTomo->numero_tomo + 1,
                    'fecha'  => \Carbon\Carbon::parse($lastTomo->fecha_publicacion)
                                      ->addMonth()
                                      ->format('Y-m-d')
                ];
            } else {
                $nextTomos[$manga->id] = [
                    'numero' => 1,
                    'fecha'  => null
                ];
            }
        }

        // Obtener todos los tomos con stock menor a 10 (sin depender de los filtros)
        $lowStockTomos = Tomo::where('stock', '<', 10)->with('manga')->get();
        $hasLowStock   = $lowStockTomos->isNotEmpty();

        return view('tomos.index', compact('tomos', 'mangas', 'editoriales', 'nextTomos', 'lowStockTomos', 'hasLowStock'));
    }

    public function store(Request $request)
    {
        // Obtener el número siguiente de tomo
        $nextNumero = Tomo::where('manga_id', $request->input('manga_id'))
                          ->max('numero_tomo') + 1;

        // Validar los campos
        $validated = $request->validate([
            'manga_id'   => 'required|exists:mangas,id',
            'numero'     => 'nullable|numeric|unique:tomos,numero,NULL,id,manga_id,' . $request->manga_id,
            'precio'     => 'required|numeric',
            'editorial_id' => 'required|exists:editoriales,id',
            'numero_tomo' => 'required|numeric|unique:tomos,numero_tomo,NULL,id,manga_id,' . $request->manga_id,
            'formato'    => 'required|in:Tankōbon,Aizōban,Kanzenban,Bunkoban,Wideban',
            'idioma'     => 'required|in:Español,Inglés,Japonés',
            'stock'      => 'nullable|numeric|min:0',
            'fecha_publicacion' => 'required|date|before:' . date('Y-m-d'),
            'portada'    => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // máx 5MB
        ]);

        // Verificar si se recibió un archivo válido
        if (! $request->hasFile('portada') || ! $request->file('portada')->isValid()) {
            return back()->withErrors(['portada' => 'No se recibió una imagen válida.']);
        }

        $file = $request->file('portada');

        // Obtener el manga para usar su título en la carpeta
        $manga = Manga::findOrFail($validated['manga_id']);
        $slug = Str::slug($manga->titulo);

        // Crear ruta temporal para guardar la imagen procesada
        $extension = $file->getClientOriginalExtension();
        $tempPath = sys_get_temp_dir() . "/portada_{$nextNumero}." . $extension;
        $file->move(sys_get_temp_dir(), "portada_{$nextNumero}." . $extension);

        // Subir la imagen procesada a Cloudinary
        $upload = (new UploadApi())->upload($tempPath, [
            'folder'    => "tomo_portadas/{$slug}",
            'public_id' => "portada_{$nextNumero}",
        ]);

        // Guardar información en base de datos
        $validated['numero']     = $nextNumero;
        $validated['portada']    = $upload['secure_url'];
        $validated['public_id']  = $upload['public_id'];
        $validated['stock']      = $validated['stock'] ?? 0;

        Tomo::create($validated);

        // Eliminar imagen temporal
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
