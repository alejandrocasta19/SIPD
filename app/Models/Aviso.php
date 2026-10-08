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
    public const TIPO_FIRMA = 'firma_pendiente';
    public const TIPO_SESION = 'actividad_sesion';
    public const TIPO_RECUPERACION = 'recuperacion_contrasena';

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
        'tipo_documento',
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
            self::TIPO_AVISO     => 'Aviso de coordinación',
            self::TIPO_PERMISO   => 'Permiso respondido',
            self::TIPO_SOLICITUD => 'Solicitud de permiso',
            self::TIPO_VEREDICTO => 'Requiere veredicto',
            self::TIPO_FIRMA     => 'Firma pendiente',
            self::TIPO_SESION    => 'Actividad de sesión',
            self::TIPO_RECUPERACION => 'Recuperación de contraseña',
        ];

        return $etiquetas[$this->tipo] ?? 'Notificación';
    }

    public function icono(): string
    {
        $iconos = [
            self::TIPO_AVISO     => 'fa-shield-alt',
            self::TIPO_PERMISO   => 'fa-check-circle',
            self::TIPO_SOLICITUD => 'fa-user-lock',
            self::TIPO_VEREDICTO => 'fa-gavel',
            self::TIPO_FIRMA     => 'fa-file-signature',
            self::TIPO_SESION    => 'fa-sign-in-alt',
            self::TIPO_RECUPERACION => 'fa-key',
        ];

        return $iconos[$this->tipo] ?? 'fa-bell';
    }

    public function tonoIcono(): string
    {
        $tonos = [
            self::TIPO_AVISO     => 'coord',
            self::TIPO_PERMISO   => 'ok',
            self::TIPO_SOLICITUD => 'warn',
            self::TIPO_VEREDICTO => 'danger',
            self::TIPO_FIRMA     => 'warn',
            self::TIPO_SESION    => 'info',
            self::TIPO_RECUPERACION => 'warn',
        ];

        return $tonos[$this->tipo] ?? 'info';
    }

    /**
     * Clase CSS semántica para el color de borde e ícono de cada tipo.
     * danger=rojo, warn=ámbar, ok=verde, coord=violeta, info=azul
     */
    public function colorClase(): string
    {
        return $this->tonoIcono();
    }

    public static function enviar(User $destinatario, array $data): self
    {
        return self::create(array_merge($data, [
            'user_id' => $destinatario->id,
        ]));
    }

    public static function aCoordinadoras(array $data, ?int $exceptUserId = null): Collection
    {
        $coords = User::where('role', User::ROLE_ADMIN)
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->get();

        return $coords->map(function (User $coord) use ($data) {
            return self::enviar($coord, $data);
        });
    }

    public static function registrarActividadSesion(User $usuario, bool $inicio): Collection
    {
        if (!$usuario->esEquipo()) {
            return collect();
        }

        $accion = $inicio ? 'inició sesión' : 'cerró sesión';
        $fechaHora = now()->format('d/m/Y H:i:s');

        return self::aCoordinadoras([
            'remitente_id' => $usuario->id,
            'tipo' => self::TIPO_SESION,
            'titulo' => $inicio ? 'Inicio de sesión del equipo' : 'Cierre de sesión del equipo',
            'cuerpo' => $usuario->name . ' ' . $accion . '. Fecha y hora: ' . $fechaHora . '.',
        ], $usuario->id);
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
