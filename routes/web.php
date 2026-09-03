<?php


use Illuminate\Support\Facades\Route;//Creacion de direcciones
use Illuminate\Support\Facades\Auth;//creacion de autenticacion
use App\Models\User;//consulta usuario
use App\Http\Controllers\HomeController; //acceso directo ddentro del archivo (importar clase)
use App\Http\Controllers\RolController;
use App\Http\Controllers\PersonalController;


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
Auth::routes([
    'reset' => false, // Desactiva las rutas de recuperación de contraseña
    'register' => false, // Opcional: desactiva el registro público si tampoco lo usas
]);

// 3. Panel Principal (Dashboard) tras iniciar sesión
Route::get('/home', [HomeController::class, 'index'])->name('home');

// 4. Ruta para probar y ver la lista de usuarios (Protegida con login)
Route::get('/probando-usuarios', function () {
    $usuarios = User::all();
    return view('users.index', compact('usuarios'));
})->middleware('auth')->name('users.index');


Route::middleware(['auth'])->group(function () {
    Route::resource('roles', App\Http\Controllers\RolController::class);
    Route::resource('personal', App\Http\Controllers\PersonalController::class);
});