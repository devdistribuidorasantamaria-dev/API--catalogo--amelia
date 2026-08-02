<?php

use App\Http\Controllers\Admin\AjusteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PrendaController;
use App\Http\Controllers\Admin\SeccionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// El backend sólo sirve el panel; la parte pública vive en el frontend Next.js.
Route::redirect('/', '/admin');

Route::middleware('auth')->group(function () {
    Route::redirect('/dashboard', '/admin')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('prendas', PrendaController::class)
            ->except('show')
            ->parameters(['prendas' => 'prenda']);

        Route::resource('secciones', SeccionController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['secciones' => 'seccion']);

        Route::get('ajustes', [AjusteController::class, 'edit'])->name('ajustes.edit');
        Route::put('ajustes', [AjusteController::class, 'update'])->name('ajustes.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
