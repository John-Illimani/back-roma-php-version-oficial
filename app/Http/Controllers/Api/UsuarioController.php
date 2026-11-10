<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Administrador;
use App\Models\Secretaria;
use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    public function index()
    {
        return response()->json(
            Usuario::with(['administrador', 'secretaria', 'postulante'])->get()
        );
    }

    public function store(Request $request)
    {
        // 1. Validar únicamente los datos reales requeridos
        $validated = $request->validate([
            'rol' => 'required|in:administrador,secretaria,postulante',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'ci' => 'required|string|max:20|unique:usuarios,ci',
            'celular' => 'nullable|string|max:20',
            'foto' => 'nullable',
            // Campos opcionales específicos para tablas hijas si aplican
            'ventanilla_asignada' => 'nullable|string|max:50',
            'turno' => 'nullable|string|max:50',
            'direccion' => 'nullable|string',
            'fecha_nacimiento' => 'nullable|date',
            'uuid' => 'nullable|string|max:50|unique:usuarios,uuid',
            
        ]);

        // 2. Extraer primer nombre y primer apellido limpios (sin espacios ni acentos)
        $primerNombre = Str::slug(explode(' ', trim($validated['nombre']))[0], '');
        $primerApellido = Str::slug(explode(' ', trim($validated['apellido']))[0], '');
        $ciLimpio = preg_replace('/[^0-9]/', '', $validated['ci']);

        // 3. Generar Username automático: @nombre_apellido
        $baseUsername = "@" . strtolower($primerNombre) . "_" . strtolower($primerApellido);
        $username = $baseUsername;
        $contador = 1;

        while (Usuario::where('username', $username)->exists()) {
            $username = $baseUsername . $contador;
            $contador++;
        }

        // 4. Generar Password inicial automático: nombre + CI + *
        $plainPassword = strtolower($primerNombre) . $ciLimpio . "*";
        $hashedPassword = hash('sha256', $plainPassword);

      $uuid = $request->input('uuid', null);

        // 6. Transacción atómica: Inserción en usuarios + tabla hija correspondiente
        $usuarioCreado = DB::transaction(function () use ($validated, $username, $hashedPassword, $uuid, $request) {
            $usuario = Usuario::create([
                'username' => $username,
                'password' => $hashedPassword,
                'rol' => $validated['rol'],
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'ci' => $validated['ci'],
                'celular' => $validated['celular'] ?? null,
                'foto' => $validated['foto'] ?? null,
                'uuid' => $uuid,
            ]);

            // Inserción en tabla hija según el rol
            if ($usuario->rol === 'administrador') {
                Administrador::create([
                    'usuario_id' => $usuario->usuario_id,
                ]);
            } elseif ($usuario->rol === 'secretaria') {
                Secretaria::create([
                    'usuario_id' => $usuario->usuario_id,
                    'ventanilla_asignada' => $request->ventanilla_asignada ?? 'Ventanilla 1',
                    'turno' => $request->turno ?? 'Continuo',
                ]);
            } elseif ($usuario->rol === 'postulante') {
                Postulante::create([
                    'usuario_id' => $usuario->usuario_id,
                    'codigo_postulante' => 'POST-' . date('Y') . '-' . rand(1000, 9999),
                    'direccion' => $request->direccion ?? null,
                    'fecha_nacimiento' => $request->fecha_nacimiento ?? null,
                ]);
            }

            return $usuario;
        });

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Usuario y perfil creados exitosamente',
            'usuario' => $usuarioCreado->load(['administrador', 'secretaria', 'postulante']),
            'credenciales_generadas' => [
                'username' => $username,
                'password_inicial' => $plainPassword
            ]
        ], 201);
    }

    public function show(int $id)
    {
        $usuario = Usuario::with(['secretaria', 'administrador', 'postulante'])->findOrFail($id);
        return response()->json($usuario);
    }

    public function update(Request $request, int $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes|string|max:60|unique:usuarios,username,' . $id . ',usuario_id',
            'password' => 'nullable|string|min:6',
            'rol' => 'sometimes|in:administrador,secretaria,postulante',
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'ci' => 'sometimes|string|max:20|unique:usuarios,ci,' . $id . ',usuario_id',
            'celular' => 'nullable|string|max:20',
            'uuid' => 'nullable|string|max:50|unique:usuarios,uuid,' . $id . ',usuario_id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = hash('sha256', $validated['password']);
        } else {
            unset($validated['password']);
        }

        $usuario->update($validated);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Usuario actualizado exitosamente',
            'usuario' => $usuario->load(['administrador', 'secretaria', 'postulante'])
        ]);
    }

    public function destroy(int $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario eliminado correctamente'
        ]);
    }
}