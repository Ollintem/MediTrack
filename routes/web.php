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

/*
|--------------------------------------------------------------------------
| Rutas Web - MediTrack
|--------------------------------------------------------------------------
*/

// Ruta de búsqueda global (requiere autenticación)
Route::middleware(['auth'])->group(function () {
    Route::get('/buscar-global', [BusquedaController::class, 'buscar'])->name('buscar.global');
});

// 1. Redirigir la ruta raíz (/) directamente al Login
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Rutas automáticas de Autenticación (Login, Logout)
Auth::routes([
    'reset'    => false, // Desactiva la recuperación de contraseña
    'register' => false, // Desactiva el registro público
]);


// 3. Rutas Protegidas por Autenticación
Route::middleware(['auth'])->group(function () {     

Route::middleware(['auth', 'permiso:Citas'])->group(function () {
    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/eventos', [CitaController::class, 'getEventos'])->name('citas.eventos'); // <--- ¡Esta es la que busca el calendario!
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('citas.update');
    Route::delete('/citas/{cita}', [CitaController::class, 'destroy'])->name('citas.destroy');
});

// Dashboard (Todos los logueados entran)
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // === MÓDULO PACIENTES ===
    // Solo entran los que pasen el middleware de "Pacientes"
    Route::middleware(['permiso:Pacientes'])->group(function () {
        // Ruta para visualizar el documento de aviso de privacidad
        Route::get('/pacientes/aviso-privacidad', [PacienteController::class, 'avisoPrivacidad'])->name('pacientes.aviso-privacidad');
        Route::resource('pacientes', PacienteController::class);
    });

    // === MÓDULO RECETAS ===
    Route::middleware(['permiso:Recetas'])->group(function () {
        Route::resource('recetas', RecetaController::class);
    });

    // === MÓDULO CONSULTAS ===
    Route::middleware(['auth', 'permiso:Consultas'])->group(function () {
    Route::get('/consultas', [ConsultaController::class, 'index'])->name('consultas.index');
});

    // === MÓDULO CONFIGURACIÓN ===
    Route::middleware(['permiso:Configuración'])->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    });

    Route::middleware(['auth', 'permiso:Citas'])->group(function () {
    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    // ... demas rutas de citas
});
    
    // Panel Principal (Dashboard)
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Módulos Principales (CRUDs completos)
    Route::resource('personal', PersonalController::class);
    Route::resource('roles', RolController::class);
    Route::resource('pacientes', PacienteController::class);
    Route::resource('consultas', ConsultaController::class);
    Route::resource('consultorios', ConsultorioController::class);

    // Módulo Recetas
    Route::get('recetas/{id}/pdf', [RecetaController::class, 'pdf'])->name('recetas.pdf');
    Route::resource('recetas', RecetaController::class)->except(['create', 'edit', 'show']);

    // Configuración del Sistema
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

    // Bitácora de Auditoría
    Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');

    // Consultorios
Route::middleware(['auth', 'permiso:Consultorios'])->group(function () {
    Route::get('/consultorios', [App\Http\Controllers\ConsultorioController::class, 'index'])->name('consultorios.index');
    Route::post('/consultorios', [App\Http\Controllers\ConsultorioController::class, 'store'])->name('consultorios.store');
    Route::put('/consultorios/{consultorio}', [App\Http\Controllers\ConsultorioController::class, 'update'])->name('consultorios.update');
    Route::delete('/consultorios/{consultorio}', [App\Http\Controllers\ConsultorioController::class, 'destroy'])->name('consultorios.destroy');
});

Route::middleware(['auth'])->group(function () {
    
    // 1. PRIMERO LA RUTA ESTÁTICA CON UNA URL DISTINTA PARA EVITAR EL CHOQUE
    Route::get('/api-citas/asignadas', [App\Http\Controllers\ConsultaController::class, 'getCitasAsignadas'])->name('consultas.citas');

    // 2. DESPUÉS EL RESOURCE DE CONSULTAS
    Route::resource('consultas', App\Http\Controllers\ConsultaController::class);

});

// Consultas y Calendario Personalizado del Doctor
Route::middleware(['auth', 'permiso:Consultas'])->group(function () {
    Route::get('/consultas', [ConsultaController::class, 'index'])->name('consultas.index');
    Route::get('/consultas/citas-asignadas', [ConsultaController::class, 'getCitasAsignadas'])->name('consultas.citas');
    Route::post('/consultas', [ConsultaController::class, 'store'])->name('consultas.store');
});


    
    // Ruta de estado
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    });
});