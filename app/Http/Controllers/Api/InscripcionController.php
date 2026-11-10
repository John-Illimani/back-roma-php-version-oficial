<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Usuario;
use App\Models\Postulante;
use App\Models\Pago;
use App\Models\HistorialEstadoInscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InscripcionController extends Controller
{
    public function index()
    {
        return response()->json(
            Inscripcion::with(['postulante.usuario', 'curso.facultad', 'pasarela', 'revisor', 'pagos'])->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'postulante_id' => 'nullable|exists:postulantes,postulante_id',
            'curso_id' => 'required|exists:cursos,curso_id',
            'pasarela_id' => 'nullable|exists:pasarela_bcp,pasarela_id',
            'turno' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'uuid' => 'nullable|string|max:100',
            'nombre' => 'required_without:postulante_id|string|max:100',
            'apellido' => 'required_without:postulante_id|string|max:100',
            'ci' => 'required_without:postulante_id|string|max:20',
            'celular' => 'nullable|string|max:20',
        ]);

        if (!empty($validated['postulante_id'])) {
            $existeMismoCurso = Inscripcion::where('postulante_id', $validated['postulante_id'])
                ->where('curso_id', $validated['curso_id'])
                ->exists();

            if ($existeMismoCurso) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El estudiante ya se encuentra inscrito en este curso específico.'
                ], 422);
            }
        }

        $resultado = DB::transaction(function () use ($validated, $request) {
            $postulanteId = $validated['postulante_id'] ?? null;
            $credenciales = null;

            if (!$postulanteId) {
                $usuarioExistente = Usuario::where('ci', $validated['ci'])->first();

                if ($usuarioExistente) {
                    $postulante = Postulante::where('usuario_id', $usuarioExistente->usuario_id)->first();
                    if ($postulante) {
                        $postulanteId = $postulante->postulante_id;
                    }
                }

                if (!$postulanteId) {
                    $primerNombre = Str::slug(explode(' ', trim($validated['nombre']))[0], '');
                    $primerApellido = Str::slug(explode(' ', trim($validated['apellido']))[0], '');
                    $ciLimpio = preg_replace('/[^0-9]/', '', $validated['ci']);

                    $baseUsername = "@" . strtolower($primerNombre) . "_" . strtolower($primerApellido);
                    $username = $baseUsername;
                    $contador = 1;
                    while (Usuario::where('username', $username)->exists()) {
                        $username = $baseUsername . $contador;
                        $contador++;
                    }

                    $plainPassword = strtolower($primerNombre) . $ciLimpio . "*";
                    $hashedPassword = hash('sha256', $plainPassword);

                    $uuid = !empty($validated['uuid']) ? $validated['uuid'] : null;

                    $usuario = Usuario::create([
                        'username' => $username,
                        'password' => $hashedPassword,
                        'rol' => 'postulante',
                        'nombre' => $validated['nombre'],
                        'apellido' => $validated['apellido'],
                        'ci' => $validated['ci'],
                        'celular' => $validated['celular'] ?? null,
                        'uuid' => $uuid,
                    ]);

                    $postulante = Postulante::create([
                        'usuario_id' => $usuario->usuario_id,
                        'codigo_postulante' => 'POST-' . date('Y') . '-' . rand(1000, 9999),
                    ]);

                    $postulanteId = $postulante->postulante_id;
                    $credenciales = [
                        'username' => $username,
                        'password_inicial' => $plainPassword,
                    ];
                }
            }

            $codigoInscripcion = 'INS-' . date('Y') . '-' . strtoupper(Str::random(6));
            $inscripcion = Inscripcion::create([
                'postulante_id' => $postulanteId,
                'curso_id' => $validated['curso_id'],
                'pasarela_id' => $validated['pasarela_id'] ?? null,
                'codigo_inscripcion' => $codigoInscripcion,
                'fecha_solicitud' => \Carbon\Carbon::now('America/La_Paz'),
                'estado' => 'pendiente',
                'observaciones' => $validated['observaciones'] ?? null,
                'turno' => $validated['turno'] ?? 'Mañana',
                'revisado_por' => $request->user()->usuario_id ?? null,
            ]);

            HistorialEstadoInscripcion::create([
                'inscripcion_id' => $inscripcion->inscripcion_id,
                'estado_anterior' => null,
                'estado_nuevo' => 'pendiente',
                'cambiado_por' => $request->user()->username ?? 'Sistema / Registro',
                'motivo' => 'Inscripción a curso',
            ]);

            return [
                'inscripcion' => $inscripcion->load(['postulante.usuario', 'curso.facultad']),
                'credenciales' => $credenciales,
            ];
        });

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Inscripción realizada exitosamente',
            'inscripcion' => $resultado['inscripcion'],
            'credenciales_generadas' => $resultado['credenciales'],
        ], 201);
    }

    public function show(int $id)
    {
        return response()->json(
            Inscripcion::with(['postulante.usuario', 'curso.facultad', 'pagos', 'historiales'])->findOrFail($id)
        );
    }

    public function update(Request $request, int $id)
    {
        $inscripcion = Inscripcion::with('postulante.usuario')->findOrFail($id);

        $validated = $request->validate([
            'curso_id' => 'sometimes|exists:cursos,curso_id',
            'turno' => 'nullable|string|max:50',
            'estado' => 'sometimes|in:pendiente,aprobado,observado,cancelado',
            'observaciones' => 'nullable|string',
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'ci' => 'sometimes|string|max:20',
            'celular' => 'nullable|string|max:20',
            'username' => 'sometimes|string|max:60',
            'password' => 'nullable|string|min:6',
            'uuid' => 'nullable|string|max:100',
            'cursos_totales' => 'nullable|array',
            'cursos_totales.*' => 'exists:cursos,curso_id',
        ]);

        DB::transaction(function () use ($inscripcion, $validated, $request) {
            $usuarioData = array_filter([
                'nombre' => $validated['nombre'] ?? null,
                'apellido' => $validated['apellido'] ?? null,
                'ci' => $validated['ci'] ?? null,
                'celular' => $validated['celular'] ?? null,
                'username' => $validated['username'] ?? null,
                'uuid' => $validated['uuid'] ?? null,
            ]);

            if (!empty($validated['password'])) {
                $usuarioData['password'] = hash('sha256', $validated['password']);
            }

            if (!empty($usuarioData)) {
                $inscripcion->postulante->usuario->update($usuarioData);
            }

            $tieneSyncCursos = $request->has('cursos_totales');

            $inscripcionData = array_filter([
                // Si viene cursos_totales, el curso_id lo resuelve el bloque de sincronización dedicado
                'curso_id' => $tieneSyncCursos ? null : ($validated['curso_id'] ?? null),
                'turno' => $validated['turno'] ?? null,
                'estado' => $validated['estado'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            if (isset($validated['estado']) && $validated['estado'] !== $inscripcion->estado) {
                HistorialEstadoInscripcion::create([
                    'inscripcion_id' => $inscripcion->inscripcion_id,
                    'estado_anterior' => $inscripcion->estado,
                    'estado_nuevo' => $validated['estado'],
                    'cambiado_por' => $request->user()->username ?? 'Administración',
                    'motivo' => 'Actualización de ficha desde panel administrativo',
                ]);
            }

            $inscripcion->update($inscripcionData);

            // SINCRONIZACIÓN DE TURNO Y OBSERVACIONES A TODOS LOS CURSOS DEL MISMO POSTULANTE
            if (isset($validated['turno']) || isset($validated['observaciones'])) {
                $syncData = [];
                if (isset($validated['turno'])) $syncData['turno'] = $validated['turno'];
                if (isset($validated['observaciones'])) $syncData['observaciones'] = $validated['observaciones'];

                Inscripcion::where('postulante_id', $inscripcion->postulante_id)
                    ->where('inscripcion_id', '!=', $inscripcion->inscripcion_id)
                    ->update($syncData);
            }

            // SINCRONIZACIÓN INTELIGENTE Y ÚNICA DE CURSOS ADICIONALES
            if ($tieneSyncCursos) {
                $cursosTotales = array_unique(array_map('intval', $request->input('cursos_totales')));
                $postulanteId = $inscripcion->postulante_id;
                
                $cursosActuales = Inscripcion::where('postulante_id', $postulanteId)
                    ->pluck('curso_id')
                    ->map(fn ($v) => (int) $v)
                    ->toArray();

                $cursosAEliminar = array_diff($cursosActuales, $cursosTotales);
                $cursosANuevo = array_diff($cursosTotales, $cursosActuales);

                if (!empty($cursosAEliminar)) {
                    Inscripcion::where('postulante_id', $postulanteId)
                        ->whereIn('curso_id', $cursosAEliminar)
                        ->delete();
                }

                foreach ($cursosANuevo as $cNuevo) {
                    $yaExiste = Inscripcion::where('postulante_id', $postulanteId)
                        ->where('curso_id', $cNuevo)
                        ->exists();

                    if ($yaExiste) continue;

                    Inscripcion::firstOrCreate(
                        [
                            'postulante_id' => $postulanteId,
                            'curso_id' => $cNuevo,
                        ],
                        [
                            'codigo_inscripcion' => 'INS-' . date('Y') . '-' . strtoupper(Str::random(6)),
                            'fecha_solicitud' => \Carbon\Carbon::now('America/La_Paz'),
                            'estado' => $validated['estado'] ?? $inscripcion->estado,
                            'turno' => $validated['turno'] ?? $inscripcion->turno,
                            'observaciones' => $validated['observaciones'] ?? null,
                        ]
                    );
                }

                // Reasignar la ficha actual al curso objetivo en caso de cambio de curso principal
                if (!empty($validated['curso_id']) && in_array((int) $validated['curso_id'], $cursosTotales, true)) {
                    $inscripcion->update(['curso_id' => $validated['curso_id']]);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Inscripción y datos actualizados correctamente',
            'inscripcion' => $inscripcion->fresh(['postulante.usuario', 'curso.facultad']),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        DB::transaction(function () use ($id) {
            $inscripcion = Inscripcion::with('postulante.usuario')->find($id);

            if ($inscripcion) {
                $postulante = $inscripcion->postulante;
                $usuario = $postulante?->usuario;

                if ($postulante) {
                    $postulante->inscripciones()->delete(); 
                    $postulante->delete();
                    if ($usuario) {
                        $usuario->delete();
                    }
                } else {
                    $inscripcion->delete();
                }
            } else {
                $postulante = Postulante::with('usuario')->find($id);
                if ($postulante) {
                    $usuario = $postulante->usuario;
                    $postulante->inscripciones()->delete();
                    $postulante->delete();
                    if ($usuario) {
                        $usuario->delete();
                    }
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Estudiante y todos sus cursos eliminados correctamente',
        ]);
    }


    /**
     * Elimina ÚNICAMENTE las inscripciones y los pagos vinculados a un estudiante.
     * MANTIENE INTACTOS los registros en las tablas 'usuarios' y 'postulantes'.
     */
    public function cancelarInscripcionesEstudiante(Request $request, int $postulanteId)
    {
        DB::transaction(function () use ($postulanteId) {
            // 1. Obtener todas las IDs de inscripciones del estudiante
            $inscripcionIds = Inscripcion::where('postulante_id', $postulanteId)
                ->pluck('inscripcion_id');

            if ($inscripcionIds->isNotEmpty()) {
                // 2. Eliminar historiales de estado vinculados
                HistorialEstadoInscripcion::whereIn('inscripcion_id', $inscripcionIds)->delete();
                
                // 3. Eliminar comprobantes/pagos asociados
                Pago::whereIn('inscripcion_id', $inscripcionIds)
                    ->orWhere('postulante_id', $postulanteId)
                    ->delete();

                // 4. Eliminar solo las inscripciones de la tabla 'inscripciones'
                Inscripcion::where('postulante_id', $postulanteId)->delete();
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Todas las materias inscritas y sus pagos fueron eliminados correctamente.',
        ], 200);
    }
}