<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificadoEmitido; // O BoletaEmitida según el modelo de tu tabla
use App\Services\BlockchainService;
use App\Http\Utils\HashUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\VistaCertificadoCompleto;
use Carbon\Carbon;

class BlockchainBoletaController extends Controller
{
    protected BlockchainService $blockchainService;

    public function __construct(BlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    public function certificar(Request $request): JsonResponse
    {
        // 1. Normalizar parámetros de entrada
        $input = [
            'postulanteId'      => $request->input('postulanteId') ?? $request->input('postulante_id'),
            'nombreCompleto'    => $request->input('nombreCompleto') ?? $request->input('nombre_completo') ?? trim(($request->input('nombre') ?? '') . ' ' . ($request->input('apellido') ?? '')),
            'ci'                => $request->input('ci'),
            'uuid'              => $request->input('uuid') ?? '',
            'reciboNro'         => $request->input('reciboNro') ?? $request->input('recibo_nro'),
            'montoAbonado'      => $request->input('montoAbonado') ?? $request->input('monto_abonado') ?? '0',
            'concepto'          => $request->input('concepto') ?? '',
            'medioPago'         => $request->input('medioPago') ?? $request->input('medio_pago') ?? '',
            'fechaPago'         => $request->input('fechaPago') ?? $request->input('fecha_pago') ?? '',
            'codigoInscripcion' => $request->input('codigoInscripcion') ?? $request->input('codigo_inscripcion') ?? '',
            'curso'             => $request->input('curso') ?? '',
        ];

        // 2. Validar parámetros obligatorios
        if (empty($input['postulanteId']) || empty($input['nombreCompleto']) || empty($input['ci']) || empty($input['reciboNro'])) {
            return response()->json([
                'success' => false,
                'message' => 'Faltan campos obligatorios para la certificación de la boleta.',
            ], 422);
        }

        $postulanteId = (int) $input['postulanteId'];
        $ci = trim((string) $input['ci']);

        try {
            // 3. Generar Hash SHA-256 Canónico de la boleta
            $rawHash = HashUtil::generarHashBoleta(
                $input['nombreCompleto'],
                $ci,
                (string) $input['uuid'],
                (string) $input['reciboNro'],
                (string) $input['montoAbonado'],
                (string) $input['concepto'],
                (string) $input['medioPago'],
                (string) $input['fechaPago'],
                (string) $input['codigoInscripcion'],
                (string) $input['curso']
            );

            // Normalización idéntica a BlockchainController (sin prefijo 0x y minúsculas)
            $hashBoleta = strtolower(str_replace('0x', '', trim($rawHash)));

            // 4. CHECK LOCAL PREVIO: Única y exclusivamente por Hash de la boleta
            $existente = CertificadoEmitido::where('hash_blockchain', $hashBoleta)->first();

            if ($existente) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Esta boleta específica ya se encuentra registrada.',
                    'hashBoletin'  => $existente->hash_blockchain,
                    'txHash'       => $existente->tx_hash,
                    'etherscanUrl' => $existente->tx_hash ? "https://sepolia.etherscan.io/tx/{$existente->tx_hash}" : null,
                    'yaExistia'    => true,
                    'registro'     => $existente,
                ], 200);
            }

            // 5. Registrar en Smart Contract / Blockchain
            $resultado = $this->blockchainService->registrarEnBlockchain(
                $hashBoleta,
                $postulanteId
            );

            // 6. Obtener y validar el txHash real de la transacción EVM (0x... de 66 caracteres)
            $txHashReal = $resultado['txHash'] ?? null;
            $esTxValida = $txHashReal && str_starts_with($txHashReal, '0x') && strlen($txHashReal) === 66;

            if (!$esTxValida && !($resultado['yaExistia'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener un Hash de Transacción válido de Sepolia.',
                    'error'   => $resultado['message'] ?? 'Error al minar la transacción en la red.',
                ], 500);
            }

            // 7. Guardar en BD Local con el TX Hash exacto de Etherscan
            $registroDb = CertificadoEmitido::create([
                'hash_blockchain' => $hashBoleta,
                'postulante_id'   => $postulanteId,
                'ci'              => $ci,
                'tx_hash'         => $txHashReal,
                'fecha_emision'   => Carbon::now('America/La_Paz'),
            ]);

            return response()->json([
                'success'      => true,
                'message'      => ($resultado['yaExistia'] ?? false)
                    ? 'La boleta ya se encontraba registrada en Blockchain.'
                    : 'Boleta de pago inmutabilizada en Blockchain con éxito.',
                'hashBoletin'  => $hashBoleta,
                'txHash'       => $txHashReal,
                'etherscanUrl' => $txHashReal ? "https://sepolia.etherscan.io/tx/{$txHashReal}" : null,
                'yaExistia'    => (bool) ($resultado['yaExistia'] ?? false),
                'registro'     => $registroDb,
            ], 200);

        } catch (Throwable $e) {
            Log::error("Error en BlockchainBoletaController@certificar: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la certificación de la boleta en Blockchain.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function verificar(Request $request): JsonResponse
    {
        // 1. Obtención flexible del parámetro (Soporta hash, txHash, tx_hash tanto en query como en body)
        $valorBuscado = $request->input('hash') 
                     ?? $request->input('txHash') 
                     ?? $request->input('tx_hash') 
                     ?? $request->query('hash') 
                     ?? $request->query('txHash') 
                     ?? $request->query('tx_hash');

        if (!$valorBuscado) {
            return response()->json([
                'success' => false,
                'message' => 'El parámetro hash o txHash es requerido para verificar.',
            ], 422);
        }

        $cadenaLimpia = trim((string) $valorBuscado);
        $hashSin0x = strtolower(str_replace('0x', '', $cadenaLimpia));

        // 2. Consulta flexible en BD Local (Busca por tx_hash O por hash_blockchain)
        $registroLocal = VistaCertificadoCompleto::where('tx_hash', $cadenaLimpia)
            ->orWhere('hash_blockchain', $hashSin0x)
            ->orWhere('hash_blockchain', $cadenaLimpia)
            ->first();

        // 3. Determinar el hash a verificar en la Blockchain
        $hashParaBlockchain = $registroLocal ? $registroLocal->hash_blockchain : $hashSin0x;

        $resultadoBlockchain = ['esValido' => false];

        // 4. Consulta de verificación en el Smart Contract
        if ($hashParaBlockchain) {
            try {
                $resultadoBlockchain = $this->blockchainService->verificarEnBlockchain($hashParaBlockchain);
            } catch (Throwable $e) {
                Log::warning("Excepción en verificación de Smart Contract por TX Hash: " . $e->getMessage());
            }
        }

        // 5. Debe coincidir en la BD Local O en el Smart Contract
        $esValido = (bool) ($registroLocal || ($resultadoBlockchain['esValido'] ?? false));

        if (!$esValido) {
            return response()->json([
                'success'   => false,
                'autentico' => false,
                'message'   => 'El Hash de Transacción o Documento no coincide con ninguna boleta válida en el sistema o Blockchain.',
            ], 404);
        }

        $txHashRetorno = $registroLocal->tx_hash ?? $cadenaLimpia;

        return response()->json([
            'success'      => true,
            'autentico'    => true,
            'estatus'      => 'Boleta Oficial Verificada en Blockchain y Base de Datos',
            'txHash'       => $txHashRetorno,
            'etherscanUrl' => "https://sepolia.etherscan.io/tx/{$txHashRetorno}",
            'registro'     => $registroLocal,
            'blockchain'   => $resultadoBlockchain,
        ], 200);
    }
}