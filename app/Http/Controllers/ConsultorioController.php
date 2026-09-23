<?php

namespace App\Http\Controllers;

use App\Models\Consultorio;
use Illuminate\Http\Request;

class ConsultorioController extends Controller
{
    public function index()
    {
        $consultorios = Consultorio::all();
        return view('consultorios.index', compact('consultorios'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clinica_id' => 'required|integer',
            'nombre'     => 'required|string|max:255',
            'piso'       => 'required|string|max:50',
            'estado'     => 'required|string',
        ]);

        Consultorio::create($data);
        return response()->json(['status' => 'success', 'message' => 'Consultorio registrado con éxito']);
    }

    public function update(Request $request, Consultorio $consultorio)
    {
        $consultorio->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Consultorio actualizado']);
    }

    public function destroy(Consultorio $consultorio)
    {
        $consultorio->delete();
        return response()->json(['status' => 'success', 'message' => 'Consultorio eliminado']);
    }
}