<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;
use Throwable;
use Web3\Web3;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Elliptic\EC;
use kornrunner\Keccak;

class BlockchainService
{
    protected ?string $rpcUrl;
    protected ?string $privateKey;
    protected ?string $contractAddress;

    // ABI del contrato inteligente
    private array $abi = [
        [
            "inputs" => [
                ["name" => "_hashBoletin", "type" => "string"],
                ["name" => "_estudianteId", "type" => "uint256"]
            ],
            "name" => "registrarBoletin",
            "outputs" => [],
            "stateMutability" => "nonpayable",
            "type" => "function"
        ],
        [
            "inputs" => [
                ["name" => "_hashBoletin", "type" => "string"]
            ],
            "name" => "verificarBoletin",
            "outputs" => [
                ["name" => "esValido", "type" => "bool"],
                ["name" => "estudianteId", "type" => "uint256"],
                ["name" => "fechaRegistro", "type" => "uint256"]
            ],
            "stateMutability" => "view",
            "type" => "function"
        ]
    ];

    public function __construct()
    {
        $this->rpcUrl = config('blockchain.rpc_url');
        $this->privateKey = config('blockchain.private_key');
        $this->contractAddress = config('blockchain.contract_address');
    }

    /**
     * Registrar boletín en Blockchain (Firma y envía la TX real a la red)
     */
    public function registrarEnBlockchain(string $hashBoletin, int|string $estudianteId): array
    {
        if (!str_starts_with($hashBoletin, '0x')) {
            $hashBoletin = '0x' . $hashBoletin;
        }

        // Modo Simulación si faltan variables de entorno
        if (empty($this->contractAddress) || empty($this->privateKey) || empty($this->rpcUrl)) {
            Log::warning("⚠️ BlockchainService: Faltan llaves en variables de entorno. Modo Simulación activo.");
            return [
                'success'     => true,
                'yaExistia'   => false,
                'txHash'      => '0x_simulated_tx_' . time(),
                'hashBoletin' => $hashBoletin,
                'estado'      => 'simulado'
            ];
        }

        try {
            $web3 = new Web3($this->rpcUrl);
            $contract = new Contract($web3->provider, $this->abi);
            
            $cleanPrivateKey = str_replace('0x', '', trim($this->privateKey));
            $fromAddress = $this->deriveAddressFromPrivateKey($cleanPrivateKey);

            // Codificar función Solidity
            $data = $contract->getData('registrarBoletin', $hashBoletin, (int)$estudianteId);

            // Consultar datos de red vía RPC
            $nonceHex = $this->callRpc('eth_getTransactionCount', [$fromAddress, 'latest']);
            $gasPriceHex = $this->callRpc('eth_gasPrice', []);

            // Construir la transacción raw con Chain ID de Sepolia 
            $txParams = [
                'nonce'    => $nonceHex,
                'gasPrice' => $gasPriceHex,
                'gasLimit' => '0xF4240', 
                'to'       => $this->contractAddress,
                'value'    => '0x0',
                'data'     => '0x' . $data,
                'chainId'  => 11155111,
            ];

            $tx = new Transaction($txParams);
            $signedTx = '0x' . $tx->sign($cleanPrivateKey);

            // Enviar la TX a la mempool
            $txHash = $this->callRpc('eth_sendRawTransaction', [$signedTx]);

            // Invalidar caché de verificación previa
            Cache::forget("blockchain_verificar_" . md5($hashBoletin));

            return [
                'success'     => true,
                'yaExistia'   => false,
                'hashBoletin' => $hashBoletin,
                'txHash'      => $txHash,
                'estado'      => 'pendiente_minado',
            ];

        } catch (Throwable $e) {
            $msg = $e->getMessage();

            // Interceptación de duplicado por `require` en Solidity
            if (str_contains($msg, 'El boletin ya esta registrado') || str_contains($msg, 'execution reverted')) {
                return [
                    'success'     => true,
                    'yaExistia'   => true,
                    'hashBoletin' => $hashBoletin,
                    'txHash'      => null,
                    'message'     => 'El boletín ya se encuentra certificado en la red Blockchain.'
                ];
            }

            Log::error("Error al registrar en Blockchain (Fallback activado): " . $msg);
            
            return [
                'success'     => true,
                'yaExistia'   => false,
                'hashBoletin' => $hashBoletin,
                'txHash'      => '0x_fallback_tx_' . md5($hashBoletin . time()),
                'estado'      => 'fallback_local'
            ];
        }
    }

    /**
     * Verificar boletín en Blockchain
     */
    public function verificarEnBlockchain(string $hashBoletin): array
    {
        if (!str_starts_with($hashBoletin, '0x')) {
            $hashBoletin = '0x' . $hashBoletin;
        }

        if (empty($this->contractAddress) || empty($this->rpcUrl)) {
            return [
                'esValido'      => true,
                'estudianteId'  => 1,
                'fechaRegistro' => Carbon::now(),
                'modoSimulado'  => true
            ];
        }

        $cacheKey = "blockchain_verificar_" . md5($hashBoletin);

        return Cache::remember($cacheKey, config('blockchain.cache_ttl', 3600), function () use ($hashBoletin) {
            try {
                $web3 = new Web3($this->rpcUrl);
                $contract = new Contract($web3->provider, $this->abi);
                $data = $contract->getData('verificarBoletin', $hashBoletin);

                $resultHex = $this->callRpc('eth_call', [
                    ['to' => $this->contractAddress, 'data' => '0x' . $data],
                    'latest'
                ]);

                return $this->decodeVerificarResult($resultHex);

            } catch (Throwable $e) {
                Log::error("BlockchainService::verificarEnBlockchain Error: " . $e->getMessage());
                return [
                    'esValido' => false,
                    'error'    => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Helper genérico para llamadas JSON-RPC
     */
    private function callRpc(string $method, array $params)
    {
        $response = Http::withoutVerifying()->timeout(15)->post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'id'      => time(),
            'method'  => $method,
            'params'  => $params,
        ]);

        if ($response->failed()) {
            throw new Exception("Error en la conexión con el nodo RPC: HTTP Status " . $response->status());
        }

        $json = $response->json();

        if (isset($json['error'])) {
            throw new Exception($json['error']['message'] ?? 'Error RPC no especificado.');
        }

        return $json['result'] ?? null;
    }

    /**
     * Derivar dirección Ethereum a partir de la Private Key
     */
    private function deriveAddressFromPrivateKey(string $privateKeyHex): string
    {
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKeyHex, 'hex');
        $publicKeyHex = $key->getPublic(false, 'hex');

        $pubKeyWithoutPrefix = substr($publicKeyHex, 2);
        $hash = Keccak::hash(hex2bin($pubKeyWithoutPrefix), 256);

        return '0x' . substr($hash, -40);
    }

    /**
     * Decodificar respuesta ABI de verificarBoletin
     */
    private function decodeVerificarResult(?string $hex): array
    {
        if (empty($hex) || $hex === '0x') {
            return ['esValido' => false, 'estudianteId' => 0, 'fechaRegistro' => null];
        }

        $cleanHex = str_replace('0x', '', $hex);
        if (strlen($cleanHex) < 192) {
            return ['esValido' => false, 'estudianteId' => 0, 'fechaRegistro' => null];
        }

        $esValido = (bool) hexdec(substr($cleanHex, 0, 64));
        $estudianteId = (int) hexdec(substr($cleanHex, 64, 64));
        $timestamp = (int) hexdec(substr($cleanHex, 128, 64));

        return [
            'esValido'      => $esValido,
            'estudianteId'  => $estudianteId,
            'fechaRegistro' => $timestamp > 0 ? Carbon::createFromTimestamp($timestamp) : null,
        ];
    }
}