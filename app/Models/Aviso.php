<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Aviso extends Model
{
    public const TIPO_VEREDICTO = 'veredicto';
    public const TIPO_SOLICITUD = 'solicitud';
    public const TIPO_PERMISO = 'permiso_respuesta';
    public const TIPO_AVISO = 'aviso';

    protected $table = 'sipd_avisos';

    protected $fillable = [
        'user_id',
        'remitente_id',
        'tipo',
        'titulo',
        'cuerpo',
        'motivo',
        'proceso_id',
        'solicitud_id',
        'leida_at',
    ];

    protected $casts = [
        'leida_at' => 'datetime',
    ];

    public function destinatario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function remitente()
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function proceso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'proceso_id');
    }

    public function solicitud()
    {
        return $this->belongsTo(PermisoSolicitud::class, 'solicitud_id');
    }

    public function estaLeida(): bool
    {
        return $this->leida_at !== null;
    }

    public function esDirectiva(): bool
    {
        return $this->tipo === self::TIPO_AVISO;
    }

    public function etiquetaTipo(): string
    {
        $etiquetas = [
            self::TIPO_AVISO => 'Aviso de coordinación',
            self::TIPO_PERMISO => 'Respuesta de permiso',
            self::TIPO_SOLICITUD => 'Solicitud de permiso',
            self::TIPO_VEREDICTO => 'Caso para veredicto',
        ];

        return $etiquetas[$this->tipo] ?? 'Notificación';
    }

    public function icono(): string
    {
        $iconos = [
            self::TIPO_AVISO => 'fa-paper-plane',
            self::TIPO_PERMISO => 'fa-key',
            self::TIPO_SOLICITUD => 'fa-user-lock',
            self::TIPO_VEREDICTO => 'fa-gavel',
        ];

        return $iconos[$this->tipo] ?? 'fa-bell';
    }

    public function tonoIcono(): string
    {
        $tonos = [
            self::TIPO_AVISO => 'coord',
            self::TIPO_PERMISO => 'ok',
            self::TIPO_SOLICITUD => 'warn',
            self::TIPO_VEREDICTO => 'info',
        ];

        return $tonos[$this->tipo] ?? 'info';
    }

    public static function enviar(User $destinatario, array $data): self
    {
        return self::create(array_merge($data, [
            'user_id' => $destinatario->id,
        ]));
    }

    public static function aCoordinadoras(array $data): Collection
    {
        $coords = User::whereIn('role', ['admin', 'coordinadora'])->get();

        return $coords->map(function (User $coord) use ($data) {
            return self::enviar($coord, $data);
        });
    }

    public static function noLeidosPara(User $user)
    {
        return self::query()
            ->where('user_id', $user->id)
            ->whereNull('leida_at')
            ->with(['remitente', 'proceso'])
            ->latest()
            ->get();
    }
}
