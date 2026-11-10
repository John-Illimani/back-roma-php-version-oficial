<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Postulante;
use Illuminate\Http\Request;

class PostulanteController extends Controller
{
    public function index()
    {
        return response()->json(Postulante::with(['usuario', 'inscripciones'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|exists:usuarios,usuario_id|unique:postulantes,usuario_id',
            'codigo_postulante' => 'required|string|max:30|unique:postulantes,codigo_postulante',
            'paralelo_id' => 'nullable|integer',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|string',
            'tutor_nombre' => 'nullable|string|max:150',
            'tutor_celular' => 'nullable|string|max:30',
            'observaciones_secretaria' => 'nullable|string'
        ]);

        $postulante = Postulante::create($validated);
        return response()->json($postulante, 201);
    }

    public function show(int $id)
    {
        return response()->json(Postulante::with(['usuario', 'inscripciones.curso'])->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $postulante = Postulante::findOrFail($id);

        $validated = $request->validate([
            'codigo_postulante' => 'sometimes|string|max:30|unique:postulantes,codigo_postulante,' . $id . ',postulante_id',
            'paralelo_id' => 'nullable|integer',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|string',
            'tutor_nombre' => 'nullable|string|max:150',
            'tutor_celular' => 'nullable|string|max:30',
            'observaciones_secretaria' => 'nullable|string'
        ]);

        $postulante->update($validated);
        return response()->json($postulante);
    }

    public function destroy(int $id)
    {
        $postulante = Postulante::findOrFail($id);
        $postulante->delete();
        return response()->json(['message' => 'Postulante eliminado correctamente']);
    }
}