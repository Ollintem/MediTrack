<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\ConsultorioController;
use App\Http\Controllers\BitacoraController;

/*
|--------------------------------------------------------------------------
| Rutas Web - MediTrack
|--------------------------------------------------------------------------
*/

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

    // Panel Principal (Dashboard) - Acceso libre para cualquier usuario logueado
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Módulo Recetas (PDF y CRUD)
    Route::get('recetas/{id}/pdf', [RecetaController::class, 'pdf'])->name('recetas.pdf');
    Route::resource('recetas', RecetaController::class)->except(['create', 'edit', 'show']);

    // Módulos principales del sistema
    Route::resource('roles', RolController::class);
    Route::resource('personal', PersonalController::class);
    Route::resource('pacientes', PacienteController::class);
    Route::resource('consultas', ConsultaController::class);
    Route::resource('consultorios', ConsultorioController::class);

    // Bitácora de Auditoría
    Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');

    // Gestión de Usuarios (Prueba interna)
    Route::get('/probando-usuarios', function () {
        $usuarios = User::all();
        return view('users.index', compact('usuarios'));
    })->name('users.index');
});