<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogoTurno;
use App\Models\CatalogoEstadoInscripcion;
use App\Models\CatalogoMedioPago;
use App\Models\CatalogoEstadoPago;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /**
     * Retorna todos los catálogos activos en una sola respuesta.
     * Ideal para inicializar los formularios en React.
     */
    public function obtenerTodos()
    {
        return response()->json([
            'turnos' => CatalogoTurno::where('activo', true)->get(),
            'estados_inscripcion' => CatalogoEstadoInscripcion::where('activo', true)->get(),
            'medios_pago' => CatalogoMedioPago::where('activo', true)->get(),
            'estados_pago' => CatalogoEstadoPago::where('activo', true)->get(),
        ]);
    }

    // ==========================================
    // 1. TURNOS
    // ==========================================
    public function indexTurnos()
    {
        return response()->json(CatalogoTurno::all());
    }

    public function storeTurno(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:30|unique:catalogo_turnos,codigo',
            'nombre' => 'required|string|max:50',
            'activo' => 'nullable|boolean',
        ]);

        $turno = CatalogoTurno::create($validated);
        return response()->json(['message' => 'Turno creado con éxito', 'data' => $turno], 201);
    }

    public function updateTurno(Request $request, int $id)
    {
        $turno = CatalogoTurno::findOrFail($id);
        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:30|unique:catalogo_turnos,codigo,' . $id . ',turno_id',
            'nombre' => 'sometimes|string|max:50',
            'activo' => 'sometimes|boolean',
        ]);

        $turno->update($validated);
        return response()->json(['message' => 'Turno actualizado con éxito', 'data' => $turno]);
    }

    public function destroyTurno(int $id)
    {
        $turno = CatalogoTurno::findOrFail($id);
        $turno->delete();
        return response()->json(['message' => 'Turno eliminado con éxito']);
    }

    // ==========================================
    // 2. ESTADOS DE INSCRIPCIÓN
    // ==========================================
    public function indexEstadosInscripcion()
    {
        return response()->json(CatalogoEstadoInscripcion::all());
    }

    public function storeEstadoInscripcion(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:30|unique:catalogo_estados_inscripcion,codigo',
            'nombre' => 'required|string|max:50',
            'descripcion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $estado = CatalogoEstadoInscripcion::create($validated);
        return response()->json(['message' => 'Estado de inscripción creado', 'data' => $estado], 201);
    }

    public function updateEstadoInscripcion(Request $request, int $id)
    {
        $estado = CatalogoEstadoInscripcion::findOrFail($id);
        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:30|unique:catalogo_estados_inscripcion,codigo,' . $id . ',estado_inscripcion_id',
            'nombre' => 'sometimes|string|max:50',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        $estado->update($validated);
        return response()->json(['message' => 'Estado de inscripción actualizado', 'data' => $estado]);
    }

    public function destroyEstadoInscripcion(int $id)
    {
        $estado = CatalogoEstadoInscripcion::findOrFail($id);
        $estado->delete();
        return response()->json(['message' => 'Estado de inscripción eliminado']);
    }

    // ==========================================
    // 3. MEDIOS DE PAGO
    // ==========================================
    public function indexMediosPago()
    {
        return response()->json(CatalogoMedioPago::all());
    }

    public function storeMedioPago(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:40|unique:catalogo_medios_pago,codigo',
            'nombre' => 'required|string|max:80',
            'activo' => 'nullable|boolean',
        ]);

        $medio = CatalogoMedioPago::create($validated);
        return response()->json(['message' => 'Medio de pago creado', 'data' => $medio], 201);
    }

    public function updateMedioPago(Request $request, int $id)
    {
        $medio = CatalogoMedioPago::findOrFail($id);
        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:40|unique:catalogo_medios_pago,codigo,' . $id . ',medio_pago_id',
            'nombre' => 'sometimes|string|max:80',
            'activo' => 'sometimes|boolean',
        ]);

        $medio->update($validated);
        return response()->json(['message' => 'Medio de pago actualizado', 'data' => $medio]);
    }

    public function destroyMedioPago(int $id)
    {
        $medio = CatalogoMedioPago::findOrFail($id);
        $medio->delete();
        return response()->json(['message' => 'Medio de pago eliminado']);
    }

    // ==========================================
    // 4. ESTADOS DE PAGO
    // ==========================================
    public function indexEstadosPago()
    {
        return response()->json(CatalogoEstadoPago::all());
    }

    public function storeEstadoPago(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:30|unique:catalogo_estados_pago,codigo',
            'nombre' => 'required|string|max:60',
            'descripcion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $estado = CatalogoEstadoPago::create($validated);
        return response()->json(['message' => 'Estado de pago creado', 'data' => $estado], 201);
    }

    public function updateEstadoPago(Request $request, int $id)
    {
        $estado = CatalogoEstadoPago::findOrFail($id);
        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:30|unique:catalogo_estados_pago,codigo,' . $id . ',estado_pago_id',
            'nombre' => 'sometimes|string|max:60',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        $estado->update($validated);
        return response()->json(['message' => 'Estado de pago actualizado', 'data' => $estado]);
    }

    public function destroyEstadoPago(int $id)
    {
        $estado = CatalogoEstadoPago::findOrFail($id);
        $estado->delete();
        return response()->json(['message' => 'Estado de pago eliminado']);
    }
}