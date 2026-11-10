<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facultad extends Model
{
    protected $table = 'facultades';
    protected $primaryKey = 'facultad_id';
    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre','estado'];

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'facultad_id', 'facultad_id');
    }
}