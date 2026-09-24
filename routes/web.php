<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ConsultorioController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\CategoriaInventarioController;

/*
|--------------------------------------------------------------------------
| Rutas Web - MediTrack
|--------------------------------------------------------------------------
*/

// 1. Redirección raíz al Login
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Autenticación (Login, Logout)
Auth::routes([
    'reset'    => false, // Desactiva recuperación pública
    'register' => false, // Desactiva registro público
]);

// 3. Rutas Protegidas por Autenticación
Route::middleware(['auth'])->group(function () {

    // --- PANEL PRINCIPAL Y BÚSQUEDA ---
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/buscar-global', [BusquedaController::class, 'buscar'])->name('buscar.global');

    // --- MÓDULO PACIENTES ---
    Route::middleware(['permiso:Pacientes'])->group(function () {
        Route::get('/pacientes/aviso-privacidad', [PacienteController::class, 'avisoPrivacidad'])->name('pacientes.aviso-privacidad');
        Route::resource('pacientes', PacienteController::class);
    });

    // --- MÓDULO CITAS ---
    Route::middleware(['permiso:Citas'])->group(function () {
        Route::get('/citas/eventos', [CitaController::class, 'getEventos'])->name('citas.eventos');
        Route::resource('citas', CitaController::class);
    });

    // --- MÓDULO CONSULTAS ---
    Route::middleware(['permiso:Consultas'])->group(function () {
        Route::get('/consultas/agenda', [ConsultaController::class, 'agenda'])->name('consultas.agenda');
        Route::get('/consultas/citas', [ConsultaController::class, 'citas'])->name('consultas.citas');
        Route::get('/consultas/detalle/{cita}', [ConsultaController::class, 'detalle'])->name('consultas.detalle');
        Route::post('/consultas/iniciar/{cita}', [ConsultaController::class, 'iniciar'])->name('consultas.iniciar');
        Route::post('/consultas/liberar/{cita}', [ConsultaController::class, 'liberar'])->name('consultas.liberar');
        Route::resource('consultas', ConsultaController::class);
    });

    // --- MÓDULO CONSULTORIOS ---
    Route::middleware(['permiso:Consultorios'])->group(function () {
        Route::get('/consultorios/estados', [ConsultorioController::class, 'estados'])->name('consultorios.estados');
        Route::patch('/consultorios/{id}/posicion', [ConsultorioController::class, 'actualizarPosicion'])->name('consultorios.posicion');
        Route::resource('consultorios', ConsultorioController::class);
    });

    // --- MÓDULO RECETAS ---
    Route::middleware(['permiso:Recetas'])->group(function () {
        Route::get('/recetas/{id}/pdf', [RecetaController::class, 'pdf'])->name('recetas.pdf');
        Route::resource('recetas', RecetaController::class);
    });

    // --- MÓDULO INVENTARIO Y CATEGORÍAS ---
    Route::middleware(['permiso:Inventario'])->group(function () {
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
        Route::put('/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update');
        Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->name('inventario.destroy');
        Route::post('/inventario/{id}/agregar-stock', [InventarioController::class, 'agregarStock'])->name('inventario.agregar-stock');

        // Rutas del Modal de Categorías de Inventario
        Route::post('/categorias-inventario', [CategoriaInventarioController::class, 'store'])->name('categorias-inventario.store');
        Route::delete('/categorias-inventario/{id}', [CategoriaInventarioController::class, 'destroy'])->name('categorias-inventario.destroy');
    });

    // --- MÓDULO ADMINISTRACIÓN Y PERFILES ---
    Route::middleware(['permiso:Personal'])->group(function () {
        Route::resource('personal', PersonalController::class);
    });

    Route::middleware(['permiso:Roles'])->group(function () {
        Route::resource('roles', RolController::class);
    });

    Route::middleware(['permiso:Configuración'])->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    });

    Route::middleware(['permiso:Bitácora'])->group(function () {
        Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    });

    // --- UTILIDAD Y ESTADO ---
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    });
});