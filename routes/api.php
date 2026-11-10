<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\FacultadController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\MateriaController;
use App\Http\Controllers\Api\PostulanteController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\BlockchainController;
use App\Http\Controllers\Api\DiplomaController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\SecretariaController;
use App\Http\Controllers\Api\BlockchainBoletaController;
use App\Http\Controllers\Api\QrInstitucionalController;


// ==========================================
// Rutas Públicas
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/blockchain/verificar/{hash}', [BlockchainController::class, 'verificarHash']);
Route::get('/blockchain/bloques', [BlockchainController::class, 'bloques']);

Route::apiResource('usuarios', UsuarioController::class);

// ==========================================
// Rutas Protegidas vía JWT
// ==========================================
Route::middleware('auth:api')->group(function () {
    // Sesión y Perfil
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // ------------------------------------------
    // Catálogos Parametrizables
    // ------------------------------------------
    // Carga conjunta para inicializar formularios
    Route::get('/catalogos/todos', [CatalogoController::class, 'obtenerTodos']);

    Route::prefix('catalogos')->group(function () {
        // Turnos Académicos
        Route::get('/turnos', [CatalogoController::class, 'indexTurnos']);
        Route::post('/turnos', [CatalogoController::class, 'storeTurno']);
        Route::put('/turnos/{id}', [CatalogoController::class, 'updateTurno']);
        Route::delete('/turnos/{id}', [CatalogoController::class, 'destroyTurno']);

        // Estados de Inscripción
        Route::get('/estados-inscripcion', [CatalogoController::class, 'indexEstadosInscripcion']);
        Route::post('/estados-inscripcion', [CatalogoController::class, 'storeEstadoInscripcion']);
        Route::put('/estados-inscripcion/{id}', [CatalogoController::class, 'updateEstadoInscripcion']);
        Route::delete('/estados-inscripcion/{id}', [CatalogoController::class, 'destroyEstadoInscripcion']);

        // Medios de Pago
        Route::get('/medios-pago', [CatalogoController::class, 'indexMediosPago']);
        Route::post('/medios-pago', [CatalogoController::class, 'storeMedioPago']);
        Route::put('/medios-pago/{id}', [CatalogoController::class, 'updateMedioPago']);
        Route::delete('/medios-pago/{id}', [CatalogoController::class, 'destroyMedioPago']);

        // Estados de Pago
        Route::get('/estados-pago', [CatalogoController::class, 'indexEstadosPago']);
        Route::post('/estados-pago', [CatalogoController::class, 'storeEstadoPago']);
        Route::put('/estados-pago/{id}', [CatalogoController::class, 'updateEstadoPago']);
        Route::delete('/estados-pago/{id}', [CatalogoController::class, 'destroyEstadoPago']);
    });

    // ------------------------------------------
    // Módulos Principales del Sistema
    // ------------------------------------------
   
    Route::apiResource('docentes', DocenteController::class);
    Route::apiResource('facultades', FacultadController::class);
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('materias', MateriaController::class);
    Route::apiResource('postulantes', PostulanteController::class);
    Route::apiResource('secretarias', SecretariaController::class);
    
    // Inscripciones con soporte CRUD completo y cambio de estado
    Route::apiResource('inscripciones', InscripcionController::class);
    Route::patch('/inscripciones/{id}/estado', [InscripcionController::class, 'cambiarEstado']);
    Route::delete('postulantes/{postulanteId}/cancelar-inscripciones', [InscripcionController::class, 'cancelarInscripcionesEstudiante']);
    // Pagos: Métodos especiales + Recurso CRUD completo
    Route::get('/pagos/total-recaudado', [PagoController::class, 'totalRecaudado']);
    Route::post('/pagos/generar-qr', [PagoController::class, 'generarPagoQr']);
    Route::post('/pagos/{id}/confirmar', [PagoController::class, 'confirmarPago']);
    Route::apiResource('pagos', PagoController::class);

    
    // Certificaciones / Diplomas
    Route::apiResource('diplomas', DiplomaController::class)->only(['index', 'store', 'show']);
    
    // Certificados Blockchain
Route::prefix('blockchain')->group(function () {
    Route::post('/certificar', [BlockchainController::class, 'certificar']);
    Route::match(['get', 'post'], '/verificar', [BlockchainController::class, 'verificar']);
});

// Boletas de Pago Blockchain
Route::prefix('blockchain/boletas')->group(function () {
    Route::post('/certificar', [BlockchainBoletaController::class, 'certificar']);
    Route::match(['get', 'post'], '/verificar', [BlockchainBoletaController::class, 'verificar']);
});

// Ruta pública para el estudiante
Route::get('/qr-institucional/activo', [QrInstitucionalController::class, 'getActivo']);

Route::prefix('qr-institucional')->group(function () {
    Route::get('/', [QrInstitucionalController::class, 'index']);
    Route::post('/', [QrInstitucionalController::class, 'store']);
    
    // Acepta tanto POST como PUT para method spoofing con FormData
    Route::match(['post', 'put'], '/{id}', [QrInstitucionalController::class, 'update']);
    
    Route::patch('/{id}/activar', [QrInstitucionalController::class, 'activar']);
    Route::delete('/{id}', [QrInstitucionalController::class, 'destroy']);
});
   
});