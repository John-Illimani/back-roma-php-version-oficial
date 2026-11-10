<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VistaCertificadoCompleto extends Model
{
    protected $table = 'vista_certificados_completos';
    
    // Especificamos la llave primaria de la vista
    protected $primaryKey = 'certificado_id';

    public $timestamps = false;
    protected $guarded = ['*'];
}