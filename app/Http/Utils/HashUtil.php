<?php

namespace App\Http\Utils;

class HashUtil
{
    /**
     * Genera un Hash SHA-256 determinista canónico para certificados.
     */
    public static function generarHashInscripcion(
        string $nombreCompleto,
        string $ci,
        string $facultad,
        string $curso,
        string $textoCertificado = ''
    ): string {
        $payload = [
            'ci'               => trim((string) $ci),
            'curso'            => mb_strtoupper(trim((string) $curso)),
            'facultad'         => mb_strtoupper(trim((string) $facultad)),
            'nombreCompleto'   => mb_strtoupper(trim((string) $nombreCompleto)),
            'textoCertificado' => trim((string) $textoCertificado),
        ];

        // Orden alfabético estricto de llaves para garantizar determinismo absoluto
        ksort($payload);

        // Encoding JSON idéntico a JavaScript
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '0x' . hash('sha256', $payloadString);
    }

   /**
     * Genera un Hash SHA-256 determinista para una Boleta de Pago.
     *
     * @param string $nombreCompleto
     * @param string $ci
     * @param string $uuid
     * @param string $reciboNro
     * @param string $montoAbonado
     * @param string $concepto
     * @param string $medioPago
     * @param string $fechaPago
     * @param string $codigoInscripcion
     * @param string $curso
     * @return string
     */
    public static function generarHashBoleta(
        string $nombreCompleto,
        string $ci,
        string $uuid,
        string $reciboNro,
        string $montoAbonado,
        string $concepto,
        string $medioPago,
        string $fechaPago,
        string $codigoInscripcion = '',
        string $curso = ''
    ): string {
        $payload = [
            'ci'                => mb_strtoupper(trim((string) $ci)),
            'codigoInscripcion' => mb_strtoupper(trim((string) $codigoInscripcion)),
            'concepto'          => mb_strtoupper(trim((string) $concepto)),
            'curso'             => mb_strtoupper(trim((string) $curso)),
            'fechaPago'         => trim((string) $fechaPago),
            'medioPago'         => mb_strtoupper(trim((string) $medioPago)),
            'montoAbonado'      => number_format((float) $montoAbonado, 2, '.', ''),
            'nombreCompleto'    => mb_strtoupper(trim((string) $nombreCompleto)),
            'reciboNro'         => trim((string) $reciboNro),
            'uuid'              => trim((string) $uuid),
        ];

        // Orden alfabético estricto para garantizar determinismo absoluto
        ksort($payload);

        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '0x' . hash('sha256', $payloadString);
    }
}