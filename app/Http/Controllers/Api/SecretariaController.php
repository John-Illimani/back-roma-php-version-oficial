<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Secretaria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class SecretariaController extends Controller
{
    // Listar todas las secretarias con su información de usuario
    public function index()
    {
        $secretarias = Secretaria::with('usuario')->get();
        return response()->json($secretarias);
    }

    // Registrar nueva secretaria y su usuario correspondiente
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'ci' => 'required|string|max:20|unique:usuarios,ci',
            'celular' => 'nullable|string|max:30',
            'ventanilla_asignada' => 'nullable|string|max:50',
            'turno' => 'nullable|string|max:30',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Generar credenciales iniciales automáticas
            $primerNombre = strtolower(explode(' ', trim($validated['nombre']))[0]);
            $primerApellido = strtolower(explode(' ', trim($validated['apellido']))[0]);
            $baseUsername = "{$primerNombre}_{$primerApellido}";
            
            $ciLimpio = preg_replace('/[^0-9]/', '', $validated['ci']);
            $passwordGenerado = "{$primerNombre}{$ciLimpio}*";

            $username = $baseUsername;
            $contador = 1;
            while (Usuario::where('username', $username)->exists()) {
                $username = "{$baseUsername}{$contador}";
                $contador++;
            }

            // 2. Crear el Usuario base con rol 'secretaria' (Usando 'password' en lugar de 'password_hash')
           // ... dentro del store ...
$usuario = Usuario::create([
    'uuid' => (string) Str::uuid(), // <--- Agregamos esta línea para satisfacer la columna uuid obligatoria
    'nombre' => $validated['nombre'],
    'apellido' => $validated['apellido'],
    'ci' => $validated['ci'],
    'celular' => $validated['celular'] ?? null,
    'username' => $username,
    'password' => Hash::make($passwordGenerado),
    'rol' => 'secretaria',
    'activo' => true,
]);

            // 3. Crear el registro en secretarias
            $secretaria = Secretaria::create([
                'usuario_id' => $usuario->usuario_id,
                'ventanilla_asignada' => $validated['ventanilla_asignada'] ?? 'Ventanilla 1',
                'turno' => $validated['turno'] ?? 'Mañana',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Secretaria registrada exitosamente',
                'secretaria' => $secretaria->load('usuario'),
                'credenciales_generadas' => [
                    'username' => $username,
                    'password_inicial' => $passwordGenerado,
                ],
            ], 201);
        });
    }

    // Mostrar una secretaria específica
    public function show(int $id)
    {
        $secretaria = Secretaria::with('usuario')->findOrFail($id);
        return response()->json($secretaria);
    }

    // Actualizar datos de secretaria y usuario
    public function update(Request $request, int $id)
    {
        $secretaria = Secretaria::with('usuario')->findOrFail($id);
        $usuario = $secretaria->usuario;

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'ci' => 'sometimes|string|max:20|unique:usuarios,ci,' . $usuario->usuario_id . ',usuario_id',
            'celular' => 'nullable|string|max:30',
            'username' => 'sometimes|string|max:50|unique:usuarios,username,' . $usuario->usuario_id . ',usuario_id',
            'password' => 'nullable|string|min:6',
            'ventanilla_asignada' => 'nullable|string|max:50',
            'turno' => 'nullable|string|max:30',
        ]);

        return DB::transaction(function () use ($validated, $secretaria, $usuario) {
            // Actualizar datos de usuario
            $usuarioData = [];
            if (isset($validated['nombre'])) $usuarioData['nombre'] = $validated['nombre'];
            if (isset($validated['apellido'])) $usuarioData['apellido'] = $validated['apellido'];
            if (isset($validated['ci'])) $usuarioData['ci'] = $validated['ci'];
            if (array_key_exists('celular', $validated)) $usuarioData['celular'] = $validated['celular'];
            if (isset($validated['username'])) $usuarioData['username'] = $validated['username'];
            if (!empty($validated['password'])) {
                // Asegurar que también se actualice usando 'password'
                $usuarioData['password'] = Hash::make($validated['password']);
            }

            if (!empty($usuarioData)) {
                $usuario->update($usuarioData);
            }

            // Actualizar datos propios de secretaria
            $secretariaData = [];
            if (array_key_exists('ventanilla_asignada', $validated)) {
                $secretariaData['ventanilla_asignada'] = $validated['ventanilla_asignada'];
            }
            if (array_key_exists('turno', $validated)) {
                $secretariaData['turno'] = $validated['turno'];
            }

            if (!empty($secretariaData)) {
                $secretaria->update($secretariaData);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Secretaria actualizada exitosamente',
                'secretaria' => $secretaria->fresh(['usuario']),
            ]);
        });
    }

    // Eliminar secretaria (y revocar el usuario asociado)
    public function destroy(int $id)
    {
        $secretaria = Secretaria::findOrFail($id);

        return DB::transaction(function () use ($secretaria) {
            $usuarioId = $secretaria->usuario_id;
            $secretaria->delete();

            if ($usuarioId) {
                Usuario::where('usuario_id', $usuarioId)->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Secretaria eliminada correctamente',
            ]);
        });
    }
}