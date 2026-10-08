<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecuperacionContrasena extends Model
{
    public const PENDIENTE = 'pendiente';
    public const ENLACE_ENVIADO = 'enlace_enviado';
    public const RECHAZADA = 'rechazada';

    protected $table = 'sipd_recuperaciones_contrasena';

    protected $fillable = [
        'user_id',
        'estado',
        'responded_by',
        'responded_at',
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
}
