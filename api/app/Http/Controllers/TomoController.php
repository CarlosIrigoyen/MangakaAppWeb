<?php

namespace App\Http\Controllers;

use App\Models\Tomo;
use App\Models\Manga;
use App\Models\Editorial;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Cloudinary\Api\Upload\UploadApi;

class TomoController extends Controller
{
     /**
     * Muestra la lista de tomos (página de administración).
     * - Permite alternar entre tomos activos e inactivos.
     * - Aplica filtros de idioma, autor, manga, editorial y búsqueda.
     * - Ordena y pagina resultados según parámetros de consulta.
     */
    public function index(Request $request)
    {
        // 1) Base de la query según filtro, cualificando 'tomos.activo'
        if ($request->get('filter_type') === 'inactivos') {
            $base = Tomo::withoutGlobalScope('activo')
                        ->where('tomos.activo', false);
        } else {
            $base = Tomo::query();
        }

        // 2) Relacionar siempre
        $query = $base->with('manga', 'editorial', 'manga.autor');

        // 3) Aplicar los demás filtros (idioma, autor, etc.) salvo si es “inactivos”
        if ($request->get('filter_type') !== 'inactivos') {
            $query = $this->applyFilters($request, $query);
        }

        // 4) Orden y paginación
        if (! $request->filled('filter_type') && ! $request->filled('search')) {
            $query->orderByDesc('created_at');
        }
        elseif ($request->filled('filter_type') && $request->get('filter_type') !== 'inactivos') {
            // Seleccionamos tomos.* antes de hacer join para evitar ambigüedad
            $query->select('tomos.*')
                  ->join('mangas', 'mangas.id', '=', 'tomos.manga_id')
                  ->orderBy('mangas.titulo', 'asc')
                  ->orderBy('tomos.numero_tomo', 'asc');
        }
        else {
            $query->orderBy('numero_tomo','asc');
        }

        $tomos         = $query->paginate(6)->appends($request->query());
        $mangas        = Manga::where('en_publicacion', 'si')->get();
        $editoriales   = Editorial::all();
        $nextTomos     = $this->getNextTomoData($mangas, $editoriales);
        $lowStockTomos = Tomo::where('stock','<',10)->with('manga')->get();
        $hasLowStock   = $lowStockTomos->isNotEmpty();

        return view('tomos.index', compact(
            'tomos','mangas','editoriales','nextTomos','lowStockTomos','hasLowStock'
        ));
    }
    /**
     * Reactiva un tomo previamente marcado como inactivo.
     * - Busca el tomo sin el scope 'activo'.
     * - Actualiza el campo activo a true.
     * - Redirige al listado de inactivos con mensaje de éxito.
     */
    public function reactivate($id, Request $request)
{
    $tomo = Tomo::withoutGlobalScope('activo')->findOrFail($id);
    $tomo->update(['activo' => true]);

    return redirect()
        ->route('tomos.index', ['filter_type' => 'inactivos'])
        ->with('success', 'Tomo reactivado correctamente.');
}

    /**
        * Aplica filtros a la consulta de tomos:
        * - idioma
        * - autor
        * - manga
        * - editorial
    */

    protected function applyFilters(Request $request, Builder $query): Builder
    {
        if ($idioma = $request->get('idioma')) {
            $query->where('idioma', $idioma);
        }
        if ($autor = $request->get('autor')) {
            $query->whereHas('manga.autor', fn($q) => $q->where('id', $autor));
        }
        if ($mangaId = $request->get('manga_id')) {
            $query->where('manga_id', $mangaId);
        }
        if ($editorialId = $request->get('editorial_id')) {
            $query->where('editorial_id', $editorialId);
        }
        if ($search = $request->get('search')) {
            $query->where(fn($q) =>
                $q->where('numero_tomo', 'like', "%{$search}%")
                  ->orWhereHas('manga', fn($q2) => $q2->where('titulo', 'like', "%{$search}%"))
                  ->orWhereHas('editorial', fn($q3) => $q3->where('nombre', 'like', "%{$search}%"))
            );
        }
        return $query;
    }
    /**
         * Genera los datos del próximo tomo para cada par manga-editorial.
         * Devuelve un array multidimensional:
     */
    protected function getNextTomoData($mangas, $editoriales): array
    {
        $result = [];
        foreach ($mangas as $manga) {
            foreach ($editoriales as $editorial) {
                $last = Tomo::withoutGlobalScope('activo')
                            ->where('manga_id', $manga->id)
                            ->where('editorial_id', $editorial->id)
                            ->orderByDesc('numero_tomo')
                            ->first();
                $result[$manga->id][$editorial->id] = $last
                    ? [
                        'numero' => $last->numero_tomo + 1,
                        'fechaMin' => Carbon::parse($last->fecha_publicacion)->addMonth()->format('Y-m-d'),
                        'precio' => $last->precio,
                        'formato' => $last->formato,
                        'idioma' => $last->idioma,
                        'readonly' => true,
                    ]
                    : [
                        'numero' => 1,
                        'fechaMin' => null,
                        'precio' => null,
                        'formato' => null,
                        'idioma' => null,
                        'readonly' => false,
                    ];
            }
        }
        return $result;
    }
        /**
        * * Almacena un nuevo tomo en la base de datos.
        * - Maneja la logica de la subida de la portada a coludinary.
        */
    public function store(Request $request)
    {
        $nextNumero = Tomo::withoutGlobalScope('activo')
                          ->where('manga_id', $request->manga_id)
                          ->where('editorial_id', $request->editorial_id)
                          ->max('numero_tomo') + 1;

        $fechaMin = null;
        if ($nextNumero > 1) {
            $ultimaFecha = Tomo::withoutGlobalScope('activo')
                               ->where('manga_id', $request->manga_id)
                               ->where('editorial_id', $request->editorial_id)
                               ->orderByDesc('numero_tomo')
                               ->value('fecha_publicacion');
            $fechaMin = Carbon::parse($ultimaFecha)->addMonth()->format('Y-m-d');
        }

        $rules = [
            'manga_id' => 'required|exists:mangas,id',
            'editorial_id' => 'required|exists:editoriales,id',
            'formato' => 'required|in:Tankōbon,Aizōban,Kanzenban,Bunkoban,Wideban',
            'idioma' => 'required|in:Español,Inglés,Japonés',
            'precio' => 'required|numeric|gt:0',
            'stock' => 'nullable|integer|min:0',
            'portada' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'fecha_publicacion' => $fechaMin ? "required|date|after_or_equal:$fechaMin" : 'required|date',
        ];
        $validated = $request->validate($rules);

        // === Cloudinary: preparar y subir la portada ===
        $file = $request->file('portada'); //se obitiene la imagen de la portada
        $slug = Str::slug(Manga::findOrFail($validated['manga_id'])->titulo); // se obtiene el slug del manga
        $ext = $file->getClientOriginalExtension(); // se obtiene la extension
        $temp = sys_get_temp_dir() . "/portada_{$nextNumero}.{$ext}"; // se crea un archivo temporal
        $file->move(sys_get_temp_dir(), basename($temp)); // se mueve el archivo a la carpeta temporal
        // se sube la portada
        // folder es la carpeta donde se van a guardar las imagenes
        // public_id es el nombre de la imagen
        $upload = (new UploadApi())->upload($temp, [
            'folder' => "tomo_portadas/$slug",
            'public_id' => "portada_{$nextNumero}",
            'transformation' => [
                [
                    'width' => 270,
                    'height' => 320,
                    'crop' => 'fill',
                ]

            ]
        ]);

        Tomo::create([
            'manga_id' => $validated['manga_id'],
            'editorial_id' => $validated['editorial_id'],
            'numero_tomo' => $nextNumero,
            'formato' => $validated['formato'],
            'idioma' => $validated['idioma'],
            'precio' => $validated['precio'],
            'fecha_publicacion' => $validated['fecha_publicacion'],
            'stock' => $validated['stock'] ?? 0,
            'portada' => $upload['secure_url'],
            'public_id' => $upload['public_id'],
            'activo' => true,
        ]);

        // se borra el archivo temporal
        @unlink($temp);
        return redirect()->route('tomos.index')->with('success', 'Tomo creado exitosamente.');
    }
    // === Actualizar un tomo ===
    public function edit($id)
    {
        $tomo = Tomo::with('manga', 'editorial')->findOrFail($id);
        $mangas = Manga::all();
        $editoriales = Editorial::all();
        return response()->json(compact('tomo', 'mangas', 'editoriales'));
    }
    //actualzar tomo con los nuevos datos recibidos
    public function update(Request $request, $id)
    {
        $tomo = Tomo::findOrFail($id);
        $rules = [
            'manga_id' => 'required|exists:mangas,id',
            'editorial_id' => 'required|exists:editoriales,id',
            'formato' => 'required|in:Tankōbon,Aizōban,Kanzenban,Bunkoban,Wideban',
            'idioma' => 'required|in:Español,Inglés,Japonés',
            'precio' => 'required|numeric|gt:0',
            'fecha_publicacion' => 'required|date|before:' . now()->toDateString(),
            'stock' => 'sometimes|integer|min:0',
        ];

        if ($request->hasFile('portada')) {
            $rules['portada'] = 'image|mimes:jpeg,png,jpg,webp|max:5120';
        }

        $validated = $request->validate($rules);
        // si se ha cambiado la portada se actualiza en cloudinary
        if ($request->hasFile('portada')) {
            $file = $request->file('portada');
            $ext = $file->getClientOriginalExtension();
            $temp = sys_get_temp_dir() . "/portada_{$tomo->numero_tomo}.{$ext}";
            $file->move(sys_get_temp_dir(), basename($temp));
            $slug = Str::slug($tomo->manga->titulo);
            $upload = (new UploadApi())->upload($temp, [
                'folder' => "tomo_portadas/$slug",
                'public_id' => "portada_{$tomo->numero_tomo}",
                'transformation' => [
                    [
                        'width'  => 270,
                        'height' => 320,
                        'crop'   => 'fill'
                    ]
                ],
            ]);
            $validated['portada'] = $upload['secure_url'];
            $validated['public_id'] = $upload['public_id'];
            @unlink($temp);
        }

        $tomo->update($validated);
        return redirect($request->input('redirect_to', route('tomos.index')))
               ->with('success', 'Tomo actualizado correctamente.');
    }
    // soft delete de un tomo
    public function destroy($id, Request $request)
    {
        $tomo = Tomo::withoutGlobalScope('activo')->findOrFail($id);
        $tomo->update(['activo' => false]);

        return redirect($request->input('redirect_to', route('tomos.index')))
               ->with('success', 'Tomo marcado como inactivo.');
    }
    //actualiza el stock de varios tomos con stock bajo
    public function updateMultipleStock(Request $request)
    {
        $request->validate([
            'tomos' => 'required|array',
            'tomos.*.id' => 'required|exists:tomos,id',
            'tomos.*.stock' => 'required|integer|min:1',
        ]);

        foreach ($request->tomos as $data) {
            Tomo::findOrFail($data['id'])->update(['stock' => $data['stock']]);
        }

        return redirect()->route('tomos.index')->with('success', 'Stocks actualizados correctamente.');
    }
    // api para obtener los tomos y mostrarlos en el front de react
    public function indexPublic(Request $request)
    {
        $query = Tomo::with('manga', 'editorial', 'manga.autor', 'manga.generos');

        if ($request->filled('authors')) {
            $ids = explode(',', $request->get('authors'));
            $query->whereHas('manga.autor', fn($q) => $q->whereIn('id', $ids));
        }
        if ($request->filled('languages')) {
            $langs = explode(',', $request->get('languages'));
            $query->whereIn('idioma', $langs);
        }
        if ($request->filled('mangas')) {
            $mids = explode(',', $request->get('mangas'));
            $query->whereIn('manga_id', $mids);
        }
        if ($request->filled('editorials')) {
            $eids = explode(',', $request->get('editorials'));
            $query->whereIn('editorial_id', $eids);
        }
        if ($search = $request->get('search')) {
            $query->where(fn($q) =>
                $q->where('numero_tomo', 'like', "%{$search}%")
                  ->orWhereHas('manga', fn($q2) => $q2->where('titulo', 'like', "%{$search}%"))
                  ->orWhereHas('editorial', fn($q3) => $q3->where('nombre', 'like', "%{$search}%"))
            );
        }
        if ($request->get('applyPriceFilter') == 1 && $request->filled(['minPrice', 'maxPrice'])) {
            $query->whereBetween('precio', [floatval($request->minPrice), floatval($request->maxPrice)]);
        }

        $query->orderByRaw("(select titulo from mangas where mangas.id = tomos.manga_id) asc")
              ->orderBy('numero_tomo', 'asc');

        $tomos = $query->paginate(8)->appends($request->query());
        return response()->json($tomos);
    }
}
