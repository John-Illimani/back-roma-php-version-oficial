<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificadoEmitido;
use App\Services\BlockchainService;
use App\Http\Utils\HashUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\VistaCertificadoCompleto;
use Carbon\Carbon;
class BlockchainController extends Controller
{
    protected BlockchainService $blockchainService;

    public function __construct(BlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    public function certificar(Request $request): JsonResponse
    {
        $input = [
            'postulanteId'     => $request->input('postulanteId') ?? $request->input('postulante_id'),
            'nombreCompleto'   => $request->input('nombreCompleto') ?? $request->input('nombre_completo'),
            'ci'               => $request->input('ci'),
            'facultad'         => $request->input('facultad'),
            'curso'            => $request->input('curso'),
            'textoCertificado' => $request->input('textoCertificado') ?? $request->input('texto_certificado') ?? '',
        ];

        if (empty($input['postulanteId']) || empty($input['nombreCompleto']) || empty($input['ci']) || empty($input['facultad']) || empty($input['curso'])) {
            return response()->json([
                'success' => false,
                'message' => 'Faltan campos obligatorios para certificar.',
            ], 422);
        }

        $postulanteId = (int) $input['postulanteId'];
        $ci = trim((string) $input['ci']);

        try {
            // 1. Calcular Hash SHA-256 Canónico en PHP
            $rawHash = HashUtil::generarHashInscripcion(
                $input['nombreCompleto'],
                $ci,
                $input['facultad'],
                $input['curso'],
                $input['textoCertificado']
            );
            $hashInscripcion = strtolower(str_replace('0x', '', trim($rawHash)));

            // 2. CHECK LOCAL PREVIO
            $existente = CertificadoEmitido::where('hash_blockchain', $hashInscripcion)->first();

            if ($existente) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Este certificado específico ya se encuentra registrado.',
                    'hashBoletin'  => $existente->hash_blockchain,
                    'txHash'       => $existente->tx_hash,
                    'etherscanUrl' => $existente->tx_hash ? "https://sepolia.etherscan.io/tx/{$existente->tx_hash}" : null,
                    'yaExistia'    => true,
                    'certificado'  => $existente,
                ], 200);
            }

            // 3. Registrar en Smart Contract / Blockchain
            $resultado = $this->blockchainService->registrarEnBlockchain(
                $hashInscripcion,
                $postulanteId
            );

            // 4. Obtener y validar txHash real
            $txHashReal = $resultado['txHash'] ?? null;
            $esTxValida = $txHashReal && str_starts_with($txHashReal, '0x') && strlen($txHashReal) === 66;

            if (!$esTxValida && !($resultado['yaExistia'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener un Hash de Transacción válido de Sepolia.',
                    'error'   => $resultado['message'] ?? 'Error al minar la transacción en la red.',
                ], 500);
            }

            // 5. Guardar en BD Local
            $certificado = CertificadoEmitido::create([
                'hash_blockchain' => $hashInscripcion,
                'postulante_id'   => $postulanteId,
                'ci'              => $ci,
                'tx_hash'         => $txHashReal,
                'fecha_emision'   => Carbon::now('America/La_Paz'),
            ]);

            return response()->json([
                'success'      => true,
                'message'      => ($resultado['yaExistia'] ?? false)
                    ? 'El certificado ya se encontraba registrado en Blockchain.'
                    : 'Certificado inmutabilizado en Blockchain con éxito.',
                'hashBoletin'  => $hashInscripcion,
                'txHash'       => $txHashReal,
                'etherscanUrl' => $txHashReal ? "https://sepolia.etherscan.io/tx/{$txHashReal}" : null,
                'yaExistia'    => (bool) ($resultado['yaExistia'] ?? false),
                'certificado'  => $certificado,
            ], 200);

        } catch (Throwable $e) {
            Log::error("Error en BlockchainController@certificar: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la certificación en Blockchain.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function verificar(Request $request): JsonResponse
    {
        // 1. Captura estricta dando prioridad a los Query Parameters de GET
        $valorBuscado = $request->query('hash') 
                     ?? $request->query('tx_hash') 
                     ?? $request->query('txHash') 
                     ?? $request->input('hash') 
                     ?? $request->input('tx_hash') 
                     ?? $request->input('txHash');

        if (!$valorBuscado) {
            return response()->json([
                'success' => false,
                'message' => 'El parámetro hash o txHash es requerido para verificar.',
            ], 422);
        }

        $cadenaLimpia = trim((string) $valorBuscado);
        $hashSin0x = strtolower(str_replace('0x', '', $cadenaLimpia));
        $hashCon0x = str_starts_with(strtolower($cadenaLimpia), '0x') ? strtolower($cadenaLimpia) : '0x' . strtolower($cadenaLimpia);

        // 2. Búsqueda exhaustiva en BD por tx_hash (con/sin 0x) y hash_blockchain
        $certificadoLocal = VistaCertificadoCompleto::where('tx_hash', $cadenaLimpia)
            ->orWhere('tx_hash', $hashCon0x)
            ->orWhere('tx_hash', $hashSin0x)
            ->orWhere('hash_blockchain', $hashSin0x)
            ->orWhere('hash_blockchain', $cadenaLimpia)
            ->first();

        // 3. Determinar el hash para consultar el Smart Contract
        $hashParaBlockchain = $certificadoLocal ? $certificadoLocal->hash_blockchain : $hashSin0x;
        $resultadoBlockchain = ['esValido' => false];

        // 4. Consulta de verificación en el Smart Contract
        if ($hashParaBlockchain) {
            try {
                $resultadoBlockchain = $this->blockchainService->verificarEnBlockchain($hashParaBlockchain);
            } catch (Throwable $e) {
                Log::warning("Excepción en verificación de Smart Contract por TX Hash: " . $e->getMessage());
            }
        }

        // 5. Validación final
        $esValido = (bool) ($certificadoLocal || ($resultadoBlockchain['esValido'] ?? false));

        if (!$esValido) {
            return response()->json([
                'success'   => false,
                'autentico' => false,
                'message'   => 'El Hash de Transacción o Documento no coincide con ningún certificado válido en el sistema o Blockchain.',
            ], 404);
        }

        $txHashRetorno = $certificadoLocal->tx_hash ?? $cadenaLimpia;

        return response()->json([
            'success'      => true,
            'autentico'    => true,
            'estatus'      => 'Documento Oficial Verificado en Blockchain y Base de Datos',
            'txHash'       => $txHashRetorno,
            'etherscanUrl' => "https://sepolia.etherscan.io/tx/{$txHashRetorno}",
            'certificado'  => $certificadoLocal,
            'blockchain'   => $resultadoBlockchain,
        ], 200);
    }
}