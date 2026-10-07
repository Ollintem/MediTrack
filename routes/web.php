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
use App\Http\Controllers\NominaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\FarmaciaDespachoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ChatbotController;

/*
|--------------------------------------------------------------------------
| Rutas Web - MediTrack
|--------------------------------------------------------------------------
*/

// 1. Redirección Raíz
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Autenticación (Login, Logout)
Auth::routes([
    'reset'    => false,
    'register' => false,
]);

// 3. Rutas Protegidas
Route::middleware(['auth'])->group(function () {

    // --- PANEL PRINCIPAL (DASHBOARD) ---
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/datos', [HomeController::class, 'datos'])->name('home.datos');

    // --- BÚSQUEDA GLOBAL DEL SIDEBAR ---
    Route::get('/buscar', [BusquedaController::class, 'buscar'])->name('buscar.global');

    // --- CHATBOT ---
    Route::middleware(['throttle:20,1'])->prefix('chatbot')->name('chatbot.')->group(function () {
        Route::post('/mensaje', [ChatbotController::class, 'mensaje'])->name('mensaje');
        Route::post('/reiniciar', [ChatbotController::class, 'reiniciar'])->name('reiniciar');
    });

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
        Route::get('/consultas/citas-asignadas', [ConsultaController::class, 'getCitasAsignadas'])->name('consultas.citas');
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
        // Catálogo principal de medicamentos
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
        Route::put('/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update');
        Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->name('inventario.destroy');

        // Acciones de Reabastecimiento de Stock
        Route::post('/inventario/{id}/agregar-stock', [InventarioController::class, 'agregarStock'])->name('inventario.agregar-stock');
        Route::post('/inventario/surtir-lote', [InventarioController::class, 'surtirLote'])->name('inventario.surtir-lote');

        // CRUD AJAX de Categorías dentro del Modal
        Route::post('/categorias-inventario', [CategoriaInventarioController::class, 'store'])->name('categorias-inventario.store');
        Route::delete('/categorias-inventario/{id}', [CategoriaInventarioController::class, 'destroy'])->name('categorias-inventario.destroy');
    });

    // --- MÓDULO PROVEEDORES ---
    Route::resource('proveedores', ProveedorController::class)->except(['create', 'show', 'edit']);

    // --- MÓDULO POS / PUNTO DE VENTA ---
    Route::middleware(['permiso:POS'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/buscar-producto', [PosController::class, 'buscarProducto'])->name('pos.buscar-producto');
        Route::post('/pos/procesar-venta', [PosController::class, 'procesarVenta'])->name('pos.procesar-venta');
        Route::post('/pos/generar-ticket', [PosController::class, 'generarTicket'])->name('pos.generar-ticket');
        
        // Rutas para ver/imprimir tickets de venta (parámetro opcional)
        Route::get('/pos/ticket/{venta?}', [PosController::class, 'generarTicket'])->name('pos.generar-ticket-get');
        Route::get('/pos/ticket/{venta}/imprimir', [PosController::class, 'ticket'])->name('pos.ticket');
    });

    // --- MÓDULO CAJA CENTRAL ---
Route::middleware(['permiso:Caja Central'])->group(function () {
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::get('/caja/ticket/{codigo}', [CajaController::class, 'buscarPorCodigo'])->name('caja.buscar-ticket'); // NUEVA
    Route::post('/caja/procesar-pago/{id}', [CajaController::class, 'procesarPago'])->name('caja.procesar-pago');
    Route::post('/caja/aperturar', [CajaController::class, 'aperturar'])->name('caja.aperturar');
    Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::post('/caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');
    Route::get('/caja/corte/{id}', [CajaController::class, 'cortePdf'])->name('caja.corte-pdf');
});

    // --- MÓDULO DESPACHO DE FARMACIA ---
    Route::middleware(['permiso:Inventario'])->group(function () {
        Route::get('/farmacia/despacho', [FarmaciaDespachoController::class, 'index'])->name('farmacia.despacho');
        Route::post('/farmacia/marcar-entregado/{id}', [FarmaciaDespachoController::class, 'marcarEntregado'])->name('farmacia.marcar-entregado');
    });

    // --- MÓDULO ADMINISTRACIÓN Y ROLES ---
    Route::middleware(['permiso:Personal'])->group(function () {
        // Acceso directo por menú
        Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');

        // Nómina (sección del módulo de Personal)
        Route::prefix('personal/nomina')->name('personal.nomina.')->group(function () {
            Route::get('/', [NominaController::class, 'index'])->name('index');
            Route::post('/', [NominaController::class, 'store'])->name('store');

            // Ficha de nómina de cada empleado
            Route::get('/empleado/{personal}', [NominaController::class, 'empleado'])->name('empleado');

            // Recibos individuales
            Route::put('/recibo/{recibo}', [NominaController::class, 'updateRecibo'])->name('recibo.update');
            Route::delete('/recibo/{recibo}', [NominaController::class, 'quitarRecibo'])->name('recibo.quitar');
            Route::post('/recibo/{recibo}/revertir', [NominaController::class, 'revertirPago'])->name('recibo.revertir');
            Route::get('/recibo/{recibo}/pdf', [NominaController::class, 'pdf'])->name('recibo.pdf');

            // Periodos
            Route::get('/{periodo}', [NominaController::class, 'show'])->name('show');
            Route::delete('/{periodo}', [NominaController::class, 'destroy'])->name('destroy');
            Route::post('/{periodo}/empleado', [NominaController::class, 'agregarEmpleado'])->name('agregar');
            Route::post('/{periodo}/pagar', [NominaController::class, 'pagar'])->name('pagar');
            Route::post('/{periodo}/estado', [NominaController::class, 'cambiarEstado'])->name('estado');
        });

        Route::resource('personal', PersonalController::class);
    });

    Route::middleware(['permiso:Roles'])->group(function () {
        Route::resource('roles', RolController::class);
    });

    // --- MÓDULO CONFIGURACIÓN ---
    Route::middleware(['permiso:Configuración'])->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        Route::post('/configuracion/cuenta', [ConfiguracionController::class, 'actualizarCuenta'])->name('configuracion.cuenta.update');
    });

    Route::middleware(['permiso:Bitácora'])->group(function () {
        Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    });

    // --- UTILIDAD Y MONITOR DE ESTADO ---
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    });
});