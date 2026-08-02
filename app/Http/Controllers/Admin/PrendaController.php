<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrendaRequest;
use App\Models\Prenda;
use App\Models\Seccion;
use App\Services\ImagenService;
use App\Services\Revalidador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrendaController extends Controller
{
    public function __construct(
        private readonly ImagenService $imagenes,
        private readonly Revalidador $revalidador,
    ) {}

    public function index(): View
    {
        $prendas = Prenda::query()
            ->with(['seccion', 'portada'])
            ->orderBy('seccion_id')
            ->ordenadas()
            ->paginate(20);

        return view('admin.prendas.index', compact('prendas'));
    }

    public function create(): View
    {
        return view('admin.prendas.form', [
            'prenda' => new Prenda(['activa' => true, 'tallas' => []]),
            'secciones' => Seccion::ordenadas()->get(),
        ]);
    }

    public function store(PrendaRequest $request): RedirectResponse
    {
        $prenda = DB::transaction(function () use ($request) {
            $prenda = Prenda::create($request->safe()->except(['fotos', 'eliminar_imagenes', 'orden_imagenes']));
            $this->sincronizarImagenes($prenda, $request);

            return $prenda;
        });

        $this->revalidador->avisar();

        return to_route('admin.prendas.edit', $prenda)->with('status', 'Prenda agregada.');
    }

    public function edit(Prenda $prenda): View
    {
        $prenda->load('imagenes');

        return view('admin.prendas.form', [
            'prenda' => $prenda,
            'secciones' => Seccion::ordenadas()->get(),
        ]);
    }

    public function update(PrendaRequest $request, Prenda $prenda): RedirectResponse
    {
        DB::transaction(function () use ($request, $prenda) {
            $prenda->update($request->safe()->except(['fotos', 'eliminar_imagenes', 'orden_imagenes']));
            $this->sincronizarImagenes($prenda, $request);
        });

        $this->revalidador->avisar();

        return to_route('admin.prendas.edit', $prenda)->with('status', 'Prenda actualizada.');
    }

    public function destroy(Prenda $prenda): RedirectResponse
    {
        DB::transaction(function () use ($prenda) {
            foreach ($prenda->imagenes as $imagen) {
                $this->imagenes->eliminar($imagen->ruta);
            }

            // Las filas de prenda_imagenes caen por la FK en cascada.
            $prenda->delete();
        });

        $this->revalidador->avisar();

        return to_route('admin.prendas.index')->with('status', 'Prenda eliminada.');
    }

    /**
     * Borra las imágenes marcadas, sube las nuevas y reordena
     * (la posición 0 es la portada del catálogo).
     */
    private function sincronizarImagenes(Prenda $prenda, PrendaRequest $request): void
    {
        $aEliminar = $request->input('eliminar_imagenes', []);

        if ($aEliminar) {
            foreach ($prenda->imagenes()->whereIn('id', $aEliminar)->get() as $imagen) {
                $this->imagenes->eliminar($imagen->ruta);
                $imagen->delete();
            }
        }

        $siguiente = (int) $prenda->imagenes()->max('orden') + 1;

        foreach ($request->file('fotos', []) as $archivo) {
            $prenda->imagenes()->create([
                'ruta' => $this->imagenes->guardar($archivo),
                'orden' => $siguiente++,
            ]);
        }

        // El campo orden_imagenes llega desde el reordenamiento en el formulario.
        $orden = collect($request->input('orden_imagenes', []))
            ->reject(fn ($id) => in_array($id, $aEliminar))
            ->values();

        if ($orden->isNotEmpty()) {
            foreach ($orden as $posicion => $id) {
                $prenda->imagenes()->whereKey($id)->update(['orden' => $posicion]);
            }
        }

        // Normaliza a 0..n para que no queden huecos tras borrados.
        foreach ($prenda->imagenes()->orderBy('orden')->orderBy('id')->get()->values() as $posicion => $imagen) {
            if ($imagen->orden !== $posicion) {
                $imagen->update(['orden' => $posicion]);
            }
        }
    }
}
