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

    /*
    |--------------------------------------------------------------------------
    | PERMISOS POR RUTA
    |--------------------------------------------------------------------------
    | Cada ruta lleva el candado "permiso:Módulo,acción" (CheckPermission).
    | Acciones: ver · crear · editar · eliminar. Si se listan varias, basta con una.
    | Sin permiso: 403 en JSON (fetch/AJAX) o regreso con aviso de error en navegación normal.
    | Los nombres de módulo son los de la tabla `modulos` / pantalla de Roles.
    */

    // --- MÓDULO PACIENTES ---
    Route::middleware('permiso:Pacientes,ver')->group(function () {
        Route::get('/pacientes/aviso-privacidad', [PacienteController::class, 'avisoPrivacidad'])->name('pacientes.aviso-privacidad');
        Route::resource('pacientes', PacienteController::class)->only(['index']);
    });
    Route::resource('pacientes', PacienteController::class)->only(['create', 'store'])->middleware('permiso:Pacientes,crear');
    Route::resource('pacientes', PacienteController::class)->only(['edit', 'update'])->middleware('permiso:Pacientes,editar');
    Route::resource('pacientes', PacienteController::class)->only(['destroy'])->middleware('permiso:Pacientes,eliminar');

    // --- MÓDULO CITAS ---
    Route::middleware('permiso:Citas,ver')->group(function () {
        Route::get('/citas/eventos', [CitaController::class, 'getEventos'])->name('citas.eventos');
        Route::resource('citas', CitaController::class)->only(['index']);
    });
    Route::resource('citas', CitaController::class)->only(['create', 'store'])->middleware('permiso:Citas,crear');
    Route::resource('citas', CitaController::class)->only(['update'])->middleware('permiso:Citas,editar');
    Route::resource('citas', CitaController::class)->only(['destroy'])->middleware('permiso:Citas,eliminar');

    // --- MÓDULO CONSULTAS ---
    Route::middleware('permiso:Consultas,ver')->group(function () {
        Route::get('/consultas/agenda', [ConsultaController::class, 'agenda'])->name('consultas.agenda');
        Route::get('/consultas/citas-asignadas', [ConsultaController::class, 'getCitasAsignadas'])->name('consultas.citas');
        Route::get('/consultas/detalle/{cita}', [ConsultaController::class, 'detalle'])->name('consultas.detalle');
        Route::resource('consultas', ConsultaController::class)->only(['index']);
    });
    // Atender: iniciar/liberar la consulta y guardar la nota médica
    Route::middleware('permiso:Consultas,crear')->group(function () {
        Route::post('/consultas/iniciar/{cita}', [ConsultaController::class, 'iniciar'])->name('consultas.iniciar');
        Route::post('/consultas/liberar/{cita}', [ConsultaController::class, 'liberar'])->name('consultas.liberar');
        Route::resource('consultas', ConsultaController::class)->only(['store']);
    });

    // --- MÓDULO CONSULTORIOS ---
    Route::middleware('permiso:Consultorios,ver')->group(function () {
        Route::get('/consultorios/estados', [ConsultorioController::class, 'estados'])->name('consultorios.estados');
        Route::resource('consultorios', ConsultorioController::class)->only(['index']);
    });
    Route::resource('consultorios', ConsultorioController::class)->only(['store'])->middleware('permiso:Consultorios,crear');
    Route::middleware('permiso:Consultorios,editar')->group(function () {
        Route::patch('/consultorios/{id}/posicion', [ConsultorioController::class, 'actualizarPosicion'])->name('consultorios.posicion');
        Route::resource('consultorios', ConsultorioController::class)->only(['update']);
    });
    Route::resource('consultorios', ConsultorioController::class)->only(['destroy'])->middleware('permiso:Consultorios,eliminar');

    // --- MÓDULO RECETAS ---
    Route::middleware('permiso:Recetas,ver')->group(function () {
        Route::get('/recetas/{id}/pdf', [RecetaController::class, 'pdf'])->name('recetas.pdf');
        Route::resource('recetas', RecetaController::class)->only(['index']);
    });
    Route::resource('recetas', RecetaController::class)->only(['create', 'store'])->middleware('permiso:Recetas,crear');
    Route::resource('recetas', RecetaController::class)->only(['destroy'])->middleware('permiso:Recetas,eliminar');

    // --- MÓDULO INVENTARIO Y CATEGORÍAS ---
    Route::middleware('permiso:Inventario,ver')->group(function () {
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    });
    Route::middleware('permiso:Inventario,crear')->group(function () {
        Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
        Route::post('/categorias-inventario', [CategoriaInventarioController::class, 'store'])->name('categorias-inventario.store');
    });
    Route::middleware('permiso:Inventario,editar')->group(function () {
        Route::put('/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update');
        // Reabastecimiento de stock
        Route::post('/inventario/{id}/agregar-stock', [InventarioController::class, 'agregarStock'])->name('inventario.agregar-stock');
        Route::post('/inventario/surtir-lote', [InventarioController::class, 'surtirLote'])->name('inventario.surtir-lote');
    });
    Route::middleware('permiso:Inventario,eliminar')->group(function () {
        Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->name('inventario.destroy');
        Route::delete('/categorias-inventario/{id}', [CategoriaInventarioController::class, 'destroy'])->name('categorias-inventario.destroy');
    });

    // --- MÓDULO PROVEEDORES ---
    Route::resource('proveedores', ProveedorController::class)->only(['index'])->parameters(['proveedores' => 'proveedor'])->middleware('permiso:Proveedores,ver');
    Route::resource('proveedores', ProveedorController::class)->only(['store'])->parameters(['proveedores' => 'proveedor'])->middleware('permiso:Proveedores,crear');
    Route::resource('proveedores', ProveedorController::class)->only(['update'])->parameters(['proveedores' => 'proveedor'])->middleware('permiso:Proveedores,editar');
    Route::resource('proveedores', ProveedorController::class)->only(['destroy'])->parameters(['proveedores' => 'proveedor'])->middleware('permiso:Proveedores,eliminar');

    // --- MÓDULO POS / PUNTO DE VENTA ---
    Route::middleware('permiso:POS,ver')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/buscar-producto', [PosController::class, 'buscarProducto'])->name('pos.buscar-producto');
    });
    // Vender = generar el ticket
    Route::post('/pos/generar-ticket', [PosController::class, 'generarTicket'])->name('pos.generar-ticket')->middleware('permiso:POS,crear');
    // Entregar lo vendido (el despacho de farmacia vive en el POS)
    Route::middleware('permiso:POS,editar')->group(function () {
        // Procesar la entrega física pasando el ID numérico
        Route::post('/pos/marcar-entregado/{id}', [PosController::class, 'marcarEntregado'])->name('pos.marcar-entregado');
        // Entrega rápida escaneando/ingresando el código de ticket (ej. TCK-XOFNZS)
        Route::post('/pos/entregar-ticket/{codigo}', [PosController::class, 'entregarTicket'])->name('pos.entregar-ticket');
    });

    // --- MÓDULO CAJA CENTRAL ---
    Route::middleware('permiso:Caja Central,ver')->group(function () {
        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::get('/caja/ticket/{codigo}', [CajaController::class, 'buscarPorCodigo'])->name('caja.buscar-ticket');
    });
    // Operar la caja = registrar movimientos de dinero (cobros, apertura, cierre)
    Route::middleware('permiso:Caja Central,crear')->group(function () {
        Route::post('/caja/procesar-pago/{id}', [CajaController::class, 'procesarPago'])->name('caja.procesar-pago');
        Route::post('/caja/aperturar', [CajaController::class, 'aperturar'])->name('caja.aperturar');
        Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    });

    // --- MÓDULO PERSONAL (incluye Nómina) ---
    Route::middleware('permiso:Personal,ver')->group(function () {
        // Acceso directo por menú
        Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');
    });

    Route::prefix('personal/nomina')->name('personal.nomina.')->group(function () {
        Route::middleware('permiso:Personal,ver')->group(function () {
            Route::get('/', [NominaController::class, 'index'])->name('index');
            // Ficha de nómina de cada empleado y recibos en PDF
            Route::get('/empleado/{personal}', [NominaController::class, 'empleado'])->name('empleado');
            Route::get('/recibo/{recibo}/pdf', [NominaController::class, 'pdf'])->name('recibo.pdf');
            Route::get('/{periodo}', [NominaController::class, 'show'])->name('show');
        });

        Route::post('/', [NominaController::class, 'store'])->name('store')->middleware('permiso:Personal,crear');

        Route::middleware('permiso:Personal,editar')->group(function () {
            Route::put('/recibo/{recibo}', [NominaController::class, 'updateRecibo'])->name('recibo.update');
            Route::post('/recibo/{recibo}/revertir', [NominaController::class, 'revertirPago'])->name('recibo.revertir');
            Route::post('/{periodo}/empleado', [NominaController::class, 'agregarEmpleado'])->name('agregar');
            Route::post('/{periodo}/pagar', [NominaController::class, 'pagar'])->name('pagar');
            Route::post('/{periodo}/estado', [NominaController::class, 'cambiarEstado'])->name('estado');
        });

        // Quitar un recibo: el controlador decide si basta "editar" o requiere "eliminar" (recibo pagado/periodo cerrado)
        Route::delete('/recibo/{recibo}', [NominaController::class, 'quitarRecibo'])->name('recibo.quitar')->middleware('permiso:Personal,editar,eliminar');
        Route::delete('/{periodo}', [NominaController::class, 'destroy'])->name('destroy')->middleware('permiso:Personal,eliminar');
    });

    // "create" va antes que "show" para que /personal/create no se tome como /personal/{id}
    Route::resource('personal', PersonalController::class)->only(['create', 'store'])->middleware('permiso:Personal,crear');
    Route::resource('personal', PersonalController::class)->only(['index'])->middleware('permiso:Personal,ver');
    Route::resource('personal', PersonalController::class)->only(['edit', 'update'])->middleware('permiso:Personal,editar');
    Route::resource('personal', PersonalController::class)->only(['destroy'])->middleware('permiso:Personal,eliminar');

    // --- MÓDULO ROLES Y PERMISOS ---
    Route::resource('roles', RolController::class)->only(['index'])->middleware('permiso:Roles,ver');
    Route::resource('roles', RolController::class)->only(['store'])->middleware('permiso:Roles,crear');
    Route::resource('roles', RolController::class)->only(['update'])->middleware('permiso:Roles,editar');
    Route::resource('roles', RolController::class)->only(['destroy'])->middleware('permiso:Roles,eliminar');

    // --- MÓDULO CONFIGURACIÓN ---
    Route::middleware('permiso:Configuración,ver')->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        // Credenciales propias del usuario en sesión (pide su contraseña actual)
        Route::put('/configuracion/cuenta', [ConfiguracionController::class, 'actualizarCuenta'])->name('configuracion.cuenta.update');
    });
    Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update')->middleware('permiso:Configuración,editar');

    // --- MÓDULO BITÁCORA (solo lectura) ---
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index')->middleware('permiso:Bitácora,ver');

    // --- UTILIDAD Y MONITOR DE ESTADO ---
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    });
});