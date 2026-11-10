<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $hashedPassword = hash('sha256', $fields['password']);

        $usuario = Usuario::where('username', $fields['username'])
            ->where('password', $hashedPassword)
            ->first();

        if (!$usuario) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Generar el token JWT para el usuario autenticado
        $token = JWTAuth::fromUser($usuario);

        return response()->json([
            'status' => 'success',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60, // Segundos
            'usuario' => [
                'usuario_id' => $usuario->usuario_id,
                'username' => $usuario->username,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'rol' => $usuario->rol,
                'uuid' => $usuario->uuid
            ]
        ]);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Token JWT invalidado con éxito']);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'token_type' => 'Bearer',
            'access_token' => auth('api')->refresh()
        ]);
    }
}