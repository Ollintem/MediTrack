<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Clinica;
use App\Models\Configuracion;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Acceso total (ID 1 o rol de Rol::ROLES_TOTALES) también para Gate/@can
        Gate::before(function (User $user, string $ability) {
            if ($user->tieneAccesoTotal()) {
                return true;
            }
        });

        // Permisos en vistas Blade: @permiso('Citas', 'crear') ... @else ... @endpermiso
        Blade::if('permiso', function (string $modulo, string $accion = 'ver') {
            $user = auth()->user();

            return $user ? $user->tienePermiso($modulo, $accion) : false;
        });

        // Compartir $clinica y $config con todas las vistas de Blade del sistema
        View::composer('*', function ($view) {
            $clinica = Clinica::first();
            $config = Configuracion::pluck('valor', 'clave')->toArray();

            $view->with('clinica', $clinica)
                 ->with('config', $config);
        });
    }
}