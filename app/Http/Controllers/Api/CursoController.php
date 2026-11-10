<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index()
    {
        return response()->json(Curso::with(['facultad', 'docente', 'materias'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'facultad_id' => 'required|exists:facultades,facultad_id',
            'docente_id' => 'nullable|exists:docentes,docente_id',
            'codigo' => 'required|string|max:30|unique:cursos,codigo',
            'nombre' => 'required|string|max:180',
            'cupo_maximo' => 'integer|min:1',
            'precio_mensual' => 'numeric|min:0',
            'horario_referencial' => 'nullable|string|max:100',
            'dias' => 'nullable|string|max:100',
            'modalidad' => 'required|in:Presencial,Virtual,Semi-presencial',
            'aula' => 'nullable|string|max:100',
            'estado' => 'required|in:activo,inactivo,cerrado',
            'descripcion' => 'nullable|string'
        ]);

        $curso = Curso::create($validated);
        return response()->json($curso, 201);
    }

    public function show(int $id)
    {
        return response()->json(Curso::with(['facultad', 'docente', 'materias'])->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $curso = Curso::findOrFail($id);

        $validated = $request->validate([
            'facultad_id' => 'sometimes|exists:facultades,facultad_id',
            'docente_id' => 'nullable|exists:docentes,docente_id',
            'codigo' => 'sometimes|string|max:30|unique:cursos,codigo,' . $id . ',curso_id',
            'nombre' => 'sometimes|string|max:180',
            'cupo_maximo' => 'integer|min:1',
            'precio_mensual' => 'numeric|min:0',
            'horario_referencial' => 'nullable|string|max:100',
            'dias' => 'nullable|string|max:100',
            'modalidad' => 'sometimes|in:Presencial,Virtual,Semi-presencial',
            'aula' => 'nullable|string|max:100',
            'estado' => 'sometimes|in:activo,inactivo,cerrado',
            'descripcion' => 'nullable|string'
        ]);

        $curso->update($validated);
        return response()->json($curso);
    }

    public function destroy(int $id)
    {
        $curso = Curso::findOrFail($id);
        $curso->delete();
        return response()->json(['message' => 'Curso eliminado correctamente']);
    }
}