<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PersonalController;

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
    'reset' => false,    // Desactiva la recuperación de contraseña
    'register' => false, // Desactiva el registro público
]);

// 3. Rutas Protegidas por Autenticación
Route::middleware(['auth'])->group(function () {

    // Panel Principal (Dashboard)
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Gestión de Usuarios (Prueba)
    Route::get('/probando-usuarios', function () {
        $usuarios = User::all();
        return view('users.index', compact('usuarios'));
    })->name('users.index');

    // Módulos CRUD completados (Incluye index, create, store, edit, update, destroy)
    Route::resource('roles', RolController::class);
    Route::resource('personal', PersonalController::class);
});