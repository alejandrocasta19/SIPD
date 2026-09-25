<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ProcesoDisciplinario extends Model
{
    use HasFactory;

    protected $table = 'disciplinario';

    protected $fillable = [

        // DATOS DEL CONDUCTOR
        'nombre',
        'cedula',
        'placa',
        'ruta',
        'modalidad',
        'telefono',

        // INFORMACIÓN DISCIPLINARIA
        'tipo_falta',
        'descripcion_falta',
        'fecha_falta',

        // DOCUMENTOS Y OBSERVACIONES
        'documento_falta',
        'observacion',
        'descargos',
        'decision_final',

        // ESTADO DEL PROCESO
        'estado',

        // USUARIO QUE REGISTRÓ
        'user_id'
    ];

    /**
     * RELACIÓN:
     * UN PROCESO PERTENECE A UN USUARIO
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}