<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    protected $table = 'usuarios';
    protected $primaryKey = 'usuario_id';
    public $timestamps = false;

    protected $fillable = [
        'username', 'password', 'rol', 'nombre', 'apellido', 
        'ci', 'celular', 'foto', 'uuid'
    ];

    protected $hidden = ['password'];

    // Identificador único para el subject del JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Claims personalizados en el payload del JWT
    public function getJWTCustomClaims()
    {
        return [
            'rol' => $this->rol,
            'ci' => $this->ci,
            'uuid' => $this->uuid,
            'nombre_completo' => $this->nombre . ' ' . $this->apellido
        ];
    }

    public function secretaria()
    {
        return $this->hasOne(Secretaria::class, 'usuario_id', 'usuario_id');
    }

    public function administrador()
    {
        return $this->hasOne(Administrador::class, 'usuario_id', 'usuario_id');
    }

    public function postulante()
    {
        return $this->hasOne(Postulante::class, 'usuario_id', 'usuario_id');
    }
}