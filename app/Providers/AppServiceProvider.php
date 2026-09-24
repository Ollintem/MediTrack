<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
        // Regla global de superadministrador para el usuario ID = 1
        Gate::before(function (User $user, string $ability) {
            if ($user->id === 1) {
                return true;
            }
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