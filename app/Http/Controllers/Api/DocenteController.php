<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use Illuminate\Http\Request;

class DocenteController extends Controller
{
    public function index()
    {
        return response()->json(Docente::with(['cursos', 'materias'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'ci' => 'required|string|max:20|unique:docentes,ci',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:120',
            'especialidad' => 'nullable|string|max:150',
            'grado_academico' => 'nullable|string|max:50',
            'activo' => 'boolean'
        ]);

        $docente = Docente::create($validated);
        return response()->json($docente, 201);
    }

    public function show(int $id)
    {
        return response()->json(Docente::with(['cursos', 'materias'])->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $docente = Docente::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'ci' => 'sometimes|string|max:20|unique:docentes,ci,' . $id . ',docente_id',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:120',
            'especialidad' => 'nullable|string|max:150',
            'grado_academico' => 'nullable|string|max:50',
            'activo' => 'boolean'
        ]);

        $docente->update($validated);
        return response()->json($docente);
    }

    public function destroy(int $id)
    {
        $docente = Docente::findOrFail($id);
        $docente->delete();
        return response()->json(['message' => 'Docente eliminado correctamente']);
    }
}