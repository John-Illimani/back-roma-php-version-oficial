<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function index()
    {
        return response()->json(Materia::with(['curso', 'docente'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'curso_id' => 'required|exists:cursos,curso_id',
            'docente_id' => 'nullable|exists:docentes,docente_id',
            'codigo' => 'required|string|max:30|unique:materias,codigo',
            'nombre' => 'required|string|max:150',
            'horas_academicas' => 'integer|min:1',
            'precio_individual' => 'numeric|min:0',
            'cupo_maximo' => 'integer|min:1',
            'aula' => 'nullable|string|max:100',
            'horario_inicio' => 'required|date_format:H:i',
            'horario_fin' => 'required|date_format:H:i',
            'dias' => 'nullable|string|max:100',
            'modalidad' => 'nullable|string|max:30',
            'enlace_virtual' => 'nullable|string'
        ]);

        $materia = Materia::create($validated);
        return response()->json($materia, 201);
    }

    public function show(int $id)
    {
        return response()->json(Materia::with(['curso', 'docente'])->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $materia = Materia::findOrFail($id);

        $validated = $request->validate([
            'curso_id' => 'sometimes|exists:cursos,curso_id',
            'docente_id' => 'nullable|exists:docentes,docente_id',
            'codigo' => 'sometimes|string|max:30|unique:materias,codigo,' . $id . ',materia_id',
            'nombre' => 'sometimes|string|max:150',
            'horas_academicas' => 'integer|min:1',
            'precio_individual' => 'numeric|min:0',
            'cupo_maximo' => 'integer|min:1',
            'aula' => 'nullable|string|max:100',
            'horario_inicio' => 'sometimes|date_format:H:i',
            'horario_fin' => 'sometimes|date_format:H:i',
            'dias' => 'nullable|string|max:100',
            'modalidad' => 'nullable|string|max:30',
            'enlace_virtual' => 'nullable|string'
        ]);

        $materia->update($validated);
        return response()->json($materia);
    }

    public function destroy(int $id)
    {
        $materia = Materia::findOrFail($id);
        $materia->delete();
        return response()->json(['message' => 'Materia eliminada correctamente']);
    }
}