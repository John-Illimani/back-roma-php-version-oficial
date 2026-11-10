<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrInstitucional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class QrInstitucionalController extends Controller
{
    public function getActivo(): JsonResponse
    {
        // Retorna todos los QRs/Cuentas que estén activos
        $qrs = QrInstitucional::where('activo', true)->get()->map(function ($qr) {
            return [
                'qr_id'         => $qr->qr_id,
                'titular'       => $qr->titular,
                'banco'         => $qr->banco,
                'numero_cuenta' => $qr->numero_cuenta,
                'qr_foto'       => $qr->qr_foto_base64,
            ];
        });

        return response()->json([
            'success' => true,
            'qrs'     => $qrs
        ], 200);
    }

    public function index(): JsonResponse
    {
        $qrs = QrInstitucional::all()->map(function ($qr) {
            return [
                'qr_id'         => $qr->qr_id,
                'titular'       => $qr->titular,
                'banco'         => $qr->banco,
                'numero_cuenta' => $qr->numero_cuenta,
                'activo'        => (bool) $qr->activo,
                'qr_foto'       => $qr->qr_foto_base64,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $qrs
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_foto'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'titular'       => 'required|string|max:100',
            'banco'         => 'nullable|string|max:50',
            'numero_cuenta' => 'nullable|string|max:50',
            'activo'        => 'nullable',
        ]);

        try {
            $qr = DB::transaction(function () use ($request, $validated) {
                $esActivo = filter_var($validated['activo'] ?? true, FILTER_VALIDATE_BOOLEAN);

                $fileContent = null;
                if ($request->hasFile('qr_foto')) {
                    $fileContent = base64_encode(
                        file_get_contents($request->file('qr_foto')->getRealPath())
                    );
                }

                return QrInstitucional::create([
                    'titular'       => $validated['titular'],
                    'banco'         => $validated['banco'] ?? null,
                    'numero_cuenta' => $validated['numero_cuenta'] ?? null,
                    'activo'        => $esActivo,
                    'qr_foto'       => $fileContent,
                ]);
            });

            $qr->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Registro guardado correctamente.',
                'qr'      => [
                    'qr_id'         => $qr->qr_id,
                    'titular'       => $qr->titular,
                    'banco'         => $qr->banco,
                    'numero_cuenta' => $qr->numero_cuenta,
                    'activo'        => (bool) $qr->activo,
                    'qr_foto'       => $qr->qr_foto_base64,
                ]
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el registro.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $qr = QrInstitucional::findOrFail($id);

        $validated = $request->validate([
            'qr_foto'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'titular'       => 'nullable|string|max:100',
            'banco'         => 'nullable|string|max:50',
            'numero_cuenta' => 'nullable|string|max:50',
            'activo'        => 'nullable',
        ]);

        try {
            DB::transaction(function () use ($request, $validated, $qr) {
                if (isset($validated['activo'])) {
                    $qr->activo = filter_var($validated['activo'], FILTER_VALIDATE_BOOLEAN);
                }

                if ($request->hasFile('qr_foto')) {
                    $qr->qr_foto = base64_encode(
                        file_get_contents($request->file('qr_foto')->getRealPath())
                    );
                }

                if (!empty($validated['titular'])) {
                    $qr->titular = $validated['titular'];
                }
                if ($request->has('banco')) {
                    $qr->banco = $validated['banco'];
                }
                if ($request->has('numero_cuenta')) {
                    $qr->numero_cuenta = $validated['numero_cuenta'];
                }

                $qr->save();
            });

            $qr->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente.',
                'qr'      => [
                    'qr_id'         => $qr->qr_id,
                    'titular'       => $qr->titular,
                    'banco'         => $qr->banco,
                    'numero_cuenta' => $qr->numero_cuenta,
                    'activo'        => (bool) $qr->activo,
                    'qr_foto'       => $qr->qr_foto_base64,
                ]
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function activar(int $id): JsonResponse
    {
        $qr = QrInstitucional::findOrFail($id);

        try {
            // Invierte de activo a inactivo y viceversa de forma independiente
            $qr->update(['activo' => !$qr->activo]);

            return response()->json([
                'success' => true,
                'message' => 'Estado cambiado correctamente.',
                'activo'  => (bool) $qr->activo
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $qr = QrInstitucional::findOrFail($id);
            $qr->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente.'
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}