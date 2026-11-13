<?php

use App\Models\TicketVenta;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Los tickets del POS apartan stock 25 minutos. Al vencer, el accesor ya los muestra como
 * "expirado", pero en la BD seguían "pendiente" para siempre (y así salían en reportes).
 * Esta tarea los marca en la BD. Requiere el programador de Laravel:
 *   php artisan schedule:work      (desarrollo)
 *   cron: * * * * * php artisan schedule:run   (servidor)
 */
Artisan::command('tickets:expirar', function () {
    $n = TicketVenta::expirarVencidos();
    $this->info("Tickets marcados como expirados: {$n}");
})->purpose('Marca como expirados los tickets pendientes cuya reserva de 25 minutos venció');

Schedule::command('tickets:expirar')->everyMinute()->withoutOverlapping();

/*
 * Avisos diarios de la campana: nómina con recibos por pagar (fecha de pago hoy o vencida)
 * y limpieza de avisos ya leídos con más de 60 días.
 */
Artisan::command('notificaciones:diarias', function () {
    $avisos = \App\Services\Notificador::nominaPorPagar();
    $borrados = \App\Services\Notificador::limpiar(60);
    $this->info("Avisos de nómina creados: {$avisos} · avisos viejos borrados: {$borrados}");
})->purpose('Crea los avisos diarios de nómina por pagar y borra avisos leídos con más de 60 días');

Schedule::command('notificaciones:diarias')->dailyAt('08:00')->withoutOverlapping();
