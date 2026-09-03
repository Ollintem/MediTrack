<?php


use Illuminate\Support\Facades\Route;//Creacion de direcciones
use Illuminate\Support\Facades\Auth;//creacion de autenticacion
use App\Models\User;//consulta usuario
use App\Http\Controllers\HomeController; //acceso directo ddentro del archivo (importar clase)

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Rutas Web - MediTrack
|--------------------------------------------------------------------------
*/

// 1. Redirigir la ruta raíz (/) directamente a la pantalla de Login
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Rutas automáticas de Autenticación (Login, Registro, Recuperar contraseña, Logout)
Auth::routes();

// 3. Panel Principal (Dashboard) tras iniciar sesión
Route::get('/home', [HomeController::class, 'index'])->name('home');

// 4. Ruta para probar y ver la lista de usuarios (Protegida con login)
Route::get('/probando-usuarios', function () {
    $usuarios = User::all();
    return view('users.index', compact('usuarios'));
})->middleware('auth')->name('users.index');