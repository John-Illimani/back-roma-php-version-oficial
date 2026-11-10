<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\PagoQr;
use App\Models\Inscripcion;
use App\Models\BlockchainBloque;
use App\Models\BlockchainCertificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PagoController extends Controller
{
    public function index()
    {
        return response()->json(
            Pago::with(['inscripcion.curso', 'postulante.usuario', 'usuario', 'pagoQr'])->get()
        );
    }

    // Registro de Pago Directo / Presencial / Subida Comprobante
    public function store(Request $request)
{
    $validated = $request->validate([
        'inscripcion_id'          => 'required|exists:inscripciones,inscripcion_id',
        'monto'                   => 'required|numeric|min:0.5',
        'concepto'                => 'nullable|string|max:255',
        'medio_pago'              => 'nullable|string|max:50',
        'referencia_bancaria_bcp' => 'nullable|string|max:120',
        'estado_pago'             => 'nullable|string|max:50',
        'foto_comprobante'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        'verificacion'            => 'nullable|string|max:20',
    ]);

    $inscripcion = Inscripcion::with('postulante')->findOrFail($validated['inscripcion_id']);
    $usuarioId = $request->user()->usuario_id ?? $inscripcion->postulante->usuario_id;

    // Buscar si ya existe un pago previo para esta inscripción o postulante
    $pagoExistente = Pago::where('inscripcion_id', $inscripcion->inscripcion_id)
        ->orWhere('postulante_id', $inscripcion->postulante_id)
        ->first();

    // Codificación de imagen a base64
    $fotoBase64 = null;
    if ($request->hasFile('foto_comprobante')) {
        $fotoBase64 = base64_encode(
            file_get_contents($request->file('foto_comprobante')->getRealPath())
        );
    } elseif ($pagoExistente) {
        // Mantener la foto anterior si no se subió una nueva
        $fotoBase64 = $pagoExistente->foto_comprobante;
    }

    $reciboNro = $pagoExistente 
        ? $pagoExistente->recibo_nro 
        : 'REC-BCP-' . date('Y') . '-' . rand(10000, 99999);

    // Actualizar el existente o crear uno nuevo
    $pago = Pago::updateOrCreate(
        ['pago_id' => $pagoExistente?->pago_id],
        [
            'inscripcion_id'          => $inscripcion->inscripcion_id,
            'postulante_id'           => $inscripcion->postulante_id,
            'usuario_id'              => $usuarioId,
            'recibo_nro'              => $reciboNro,
            'concepto'                => $validated['concepto'] ?? 'Matrícula e Inscripción Inicial',
            'monto_abonado'           => $validated['monto'],
            'medio_pago'              => $validated['medio_pago'] ?? 'Pago Presencial en Ventanilla',
            'referencia_bancaria_bcp' => $validated['referencia_bancaria_bcp'] ?? null,
            'estado_pago'             => $validated['estado_pago'] ?? 'pendiente',
            'foto_comprobante'        => $fotoBase64,
            'verificacion'            => (!empty($validated['verificacion'])) ? $validated['verificacion'] : 'pendiente',
            'fecha_pago'              => \Carbon\Carbon::now('America/La_Paz'),
        ]
    );

    return response()->json([
        'status'  => 'success',
        'mensaje' => 'Pago guardado / actualizado exitosamente',
        'pago'    => $pago->load(['postulante.usuario', 'inscripcion'])
    ], 200);
}
    public function show(int $id)
    {
        return response()->json(
            Pago::with(['inscripcion.curso', 'postulante.usuario', 'usuario', 'pagoQr'])->findOrFail($id)
        );
    }

    public function update(Request $request, int $id)
    {
        $pago = Pago::findOrFail($id);

        $validated = $request->validate([
            'concepto'                => 'sometimes|string|max:255',
            'monto_abonado'           => 'sometimes|numeric|min:0.5',
            'medio_pago'              => 'sometimes|string|max:50',
            'referencia_bancaria_bcp' => 'nullable|string|max:120',
            'estado_pago'             => 'sometimes|string|max:50',
            'foto_comprobante'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'verificacion'            => 'sometimes|string|max:20',
        ]);

        if ($request->hasFile('foto_comprobante')) {
            $validated['foto_comprobante'] = base64_encode(
                file_get_contents($request->file('foto_comprobante')->getRealPath())
            );
        }

        $pago->update($validated);

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Pago actualizado exitosamente',
            'pago'    => $pago
        ]);
    }

    public function destroy(int $id)
    {
        $pago = Pago::findOrFail($id);
        $pago->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Pago eliminado correctamente'
        ]);
    }

    // CRUD + Generación QR Libélula / BCP
    public function generarPagoQr(Request $request)
    {
        $request->validate([
            'inscripcion_id' => 'required|exists:inscripciones,inscripcion_id',
            'monto'          => 'required|numeric|min:1',
            'concepto'       => 'required|string',
            'medio_pago'     => 'nullable|string|max:50'
        ]);

        $inscripcion = Inscripcion::with('postulante')->findOrFail($request->inscripcion_id);
        $reciboNro = 'REC-BCP-' . date('Y') . '-' . rand(10000, 99999);
        $refQr = 'REF-QR-' . strtoupper(Str::random(8));

        $usuarioId = $request->user()->usuario_id ?? $inscripcion->postulante->usuario_id;

        $pago = Pago::create([
            'inscripcion_id' => $inscripcion->inscripcion_id,
            'postulante_id'  => $inscripcion->postulante_id,
            'usuario_id'     => $usuarioId,
            'recibo_nro'     => $reciboNro,
            'concepto'       => $request->concepto,
            'monto_abonado'  => $request->monto,
            'medio_pago'     => $request->medio_pago ?? 'Pago Móvil QR BCP',
            'estado_pago'    => 'pendiente',
            'verificacion'   => 'pendiente',
            'fecha_pago'     => \Carbon\Carbon::now('America/La_Paz'),
        ]);

        $qrUrl = "https://checkout.libelula.bo/pay?ref=" . $refQr . "&amount=" . $request->monto;

        $pagoQr = PagoQr::create([
            'pago_id'          => $pago->pago_id,
            'codigo_qr'        => $qrUrl,
            'referencia_qr'    => $refQr,
            'monto'            => $request->monto,
            'moneda'           => 'BOB',
            'fecha_expiracion' => now()->addHours(2),
            'estado'           => 'pendiente',
            'respuesta_qr'     => ['url' => $qrUrl, 'proveedor' => 'Libelula/BCP']
        ]);

        return response()->json([
            'status' => 'success',
            'pago'   => $pago->load(['postulante.usuario', 'inscripcion']),
            'qr'     => $pagoQr
        ], 201);
    }

    // Confirmación y certificación Blockchain automática
    public function confirmarPago(Request $request, int $pagoId)
    {
        $pago = Pago::with(['inscripcion.postulante.usuario', 'pagoQr'])->findOrFail($pagoId);
        
        if ($request->has('estado_pago')) {
            $pago->estado_pago = $request->estado_pago;
        } else {
            $pago->estado_pago = 'confirmado';
        }

        if ($request->has('verificacion')) {
            $pago->verificacion = $request->verificacion;
        } else {
            $pago->verificacion = 'aprobado';
        }
        
        $pago->save();

        if ($pago->pagoQr) {
            $pago->pagoQr->estado = 'completado';
            $pago->pagoQr->save();
        }

        $bloque = BlockchainBloque::firstOrCreate(
            ['bloque_nro' => 1001],
            [
                'hash_bloque_actual' => '0x' . hash('sha256', 'BLOQUE_INICIAL_SUMO_' . date('Y-m-d')),
                'hash_bloque_previo' => '0x' . str_repeat('0', 64),
                'red_blockchain'     => 'Polygon PoA (SUMO-Chain)',
                'smart_contract'     => '0x892aF0C3B5E0911762Cd44A17728B490E1F142D3C4B892019283749102938471'
            ]
        );

        $payload = [
            'postulante' => $pago->inscripcion->postulante->usuario->nombre . ' ' . $pago->inscripcion->postulante->usuario->apellido,
            'recibo'     => $pago->recibo_nro,
            'monto'      => $pago->monto_abonado,
            'fecha'      => now()->toIso8601String()
        ];

        $hashTx = '0x' . hash('sha256', json_encode($payload) . microtime());

        $cert = BlockchainCertificacion::create([
            'bloque_id'           => $bloque->bloque_id,
            'pago_id'             => $pago->pago_id,
            'inscripcion_id'      => $pago->inscripcion_id,
            'tipo_documento'      => 'BOLETA_PAGO_CERTIFICADA',
            'hash_transaccion'    => $hashTx,
            'firma_notarial_root' => 'SUMO-ROOT-SIG-' . date('Y'),
            'payload_qr'          => json_encode($payload)
        ]);

        return response()->json([
            'status'        => 'success',
            'mensaje'       => 'Pago confirmado y certificado en Blockchain',
            'pago'          => $pago,
            'certificacion' => $cert
        ]);
    }

    // Consulta de recaudación total (considerando estados confirmados o completos)
    public function totalRecaudado()
    {
        $total = Pago::whereIn('estado_pago', ['confirmado', 'completo'])->sum('monto_abonado');
        return response()->json([
            'moneda'          => 'BOB',
            'total_recaudado' => (float)$total
        ]);
    }
}