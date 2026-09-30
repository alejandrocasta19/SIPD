<?php

namespace App\Models;

use App\Support\RhPermisos;
use Illuminate\Database\Eloquent\Model;

class PermisoSolicitud extends Model
{
    public const PENDIENTE = 'pendiente';
    public const OTORGADA = 'otorgada';
    public const RECHAZADA = 'rechazada';

    protected $table = 'permiso_solicitudes';

    protected $fillable = [
        'user_id',
        'permiso',
        'que_hara',
        'motivo',
        'horas',
        'estado',
        'responded_by',
        'responded_at',
        'respuesta',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function respondente()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function etiquetaPermiso(): string
    {
        return RhPermisos::etiqueta($this->permiso);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }
}
