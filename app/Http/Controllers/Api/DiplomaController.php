<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiplomaCulminacion;
use App\Models\BlockchainBloque;
use App\Models\BlockchainCertificacion;
use Illuminate\Http\Request;

class DiplomaController extends Controller
{
    public function index()
    {
        return response()->json(DiplomaCulminacion::with(['inscripcion.postulante.usuario', 'certificacion'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscripcion_id' => 'required|exists:inscripciones,inscripcion_id',
            'fecha_conclusion' => 'required|date',
            'promedio_final' => 'required|numeric|min:0|max:100',
            'calificacion_cualitativa' => 'nullable|string|max:100',
            'total_horas_academicas' => 'required|integer|min:1',
            'emitido_por' => 'nullable|string|max:120',
            'estado_emision' => 'required|in:pendiente,emitido,anulado'
        ]);

        $diploma = DiplomaCulminacion::create($validated);

        // Certificación Blockchain para el Diploma
        $bloque = BlockchainBloque::first();
        $payload = [
            'diploma_id' => $diploma->diploma_id,
            'inscripcion_id' => $diploma->inscripcion_id,
            'promedio' => $diploma->promedio_final,
            'horas' => $diploma->total_horas_academicas
        ];

        $cert = BlockchainCertificacion::create([
            'bloque_id' => $bloque->bloque_id ?? 1,
            'inscripcion_id' => $diploma->inscripcion_id,
            'diploma_id' => $diploma->diploma_id,
            'tipo_documento' => 'DIPLOMA_CULMINACION',
            'hash_transaccion' => '0x' . hash('sha256', json_encode($payload) . microtime()),
            'firma_notarial_root' => 'SUMO-ROOT-DIPLOMA-' . date('Y'),
            'payload_qr' => json_encode($payload)
        ]);

        $diploma->cert_id = $cert->cert_id;
        $diploma->save();

        return response()->json($diploma->load('certificacion'), 201);
    }

    public function show(int $id)
    {
        return response()->json(DiplomaCulminacion::with(['inscripcion.postulante.usuario', 'certificacion'])->findOrFail($id));
    }
}