<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facultad;
use Illuminate\Http\Request;

class FacultadController extends Controller
{
    public function index()
    {
        return response()->json(Facultad::with('cursos')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:facultades,codigo',
            'nombre' => 'required|string|max:150|unique:facultades,nombre',
            'estado' => 'required|string|max:20'
        ]);

        $facultad = Facultad::create($validated);
        return response()->json($facultad, 201);
    }

    public function show(int $id)
    {
        return response()->json(Facultad::with('cursos')->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $facultad = Facultad::findOrFail($id);

        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:facultades,codigo,' . $id . ',facultad_id',
            'nombre' => 'sometimes|string|max:150|unique:facultades,nombre,' . $id . ',facultad_id',
            'estado' => 'sometimes|string|max:20' . $id . ',facultad_id'
        ]);

        $facultad->update($validated);
        return response()->json($facultad);
    }

    public function destroy(int $id)
    {
        $facultad = Facultad::findOrFail($id);
        $facultad->delete();
        return response()->json(['message' => 'Facultad eliminada correctamente']);
    }
}