<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeccionRequest;
use App\Models\Seccion;
use App\Services\Revalidador;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SeccionController extends Controller
{
    public function __construct(private readonly Revalidador $revalidador) {}

    public function index(): View
    {
        return view('admin.secciones.index', [
            'secciones' => Seccion::ordenadas()->withCount('prendas')->get(),
        ]);
    }

    public function store(SeccionRequest $request): RedirectResponse
    {
        Seccion::create($request->validated());
        $this->revalidador->avisar();

        return to_route('admin.secciones.index')->with('status', 'Sección creada.');
    }

    public function update(SeccionRequest $request, Seccion $seccion): RedirectResponse
    {
        $seccion->update($request->validated());
        $this->revalidador->avisar();

        return to_route('admin.secciones.index')->with('status', 'Sección actualizada.');
    }

    public function destroy(Seccion $seccion): RedirectResponse
    {
        // Las prendas quedan sin sección (nullOnDelete), no se borran.
        $seccion->delete();
        $this->revalidador->avisar();

        return to_route('admin.secciones.index')->with('status', 'Sección eliminada. Sus prendas quedaron sin sección.');
    }
}
