<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prenda;
use App\Models\PrendaImagen;
use App\Models\Seccion;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalPrendas' => Prenda::count(),
            'prendasActivas' => Prenda::activas()->count(),
            'totalSecciones' => Seccion::count(),
            'totalFotos' => PrendaImagen::count(),
            'sinFoto' => Prenda::whereDoesntHave('imagenes')->count(),
            'recientes' => Prenda::with('seccion')->latest()->limit(5)->get(),
        ]);
    }
}
