<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;

/**
 * Bitácora: historial de movimientos que registra Bitacora::registrar() (Personal, Roles, etc.).
 * Solo lectura. El permiso "Bitácora,ver" lo revisa la ruta (middleware permiso).
 */
class BitacoraController extends Controller
{
    private const POR_PAGINA = 50;

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'modulo'  => 'nullable|string|max:100',
            'accion'  => 'nullable|string|max:100',
            'usuario' => 'nullable|string|max:100',
            'desde'   => 'nullable|date',
            'hasta'   => 'nullable|date',
        ]);

        $registros = Bitacora::query()
            ->when($filtros['modulo'] ?? null, fn ($q, $v) => $q->where('modulo', $v))
            ->when($filtros['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))
            ->when($filtros['usuario'] ?? null, fn ($q, $v) => $q->where('usuario_nombre', 'like', '%' . $v . '%'))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('bitacora.index', [
            'registros' => $registros,
            'filtros'   => $filtros,
            'modulos'   => Bitacora::query()->distinct()->orderBy('modulo')->pluck('modulo'),
            'acciones'  => Bitacora::query()->distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }
}
