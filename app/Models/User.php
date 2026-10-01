<?php

namespace App\Models;

use App\Support\RhPermisos;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cargo',
        'telefono',
        'cedula',
        'firma_path',
        'fecha_ingreso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'fecha_ingreso' => 'date',
    ];

    public function procesos()
    {
        return $this->hasMany(ProcesoDisciplinario::class, 'user_id');
    }

    public function esCoordinadora(): bool
    {
        return in_array($this->role, ['admin', 'coordinadora'], true);
    }

    public function etiquetaEquipo(): string
    {
        if ($this->esCoordinadora()) {
            return $this->cargo ?: 'Coordinadora de RH';
        }

        return $this->cargo ?: 'Equipo de RH';
    }

    public function primerNombre(): string
    {
        $partes = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $primero = $partes[0] ?? (string) $this->name;

        return mb_convert_case(mb_strtolower($primero), MB_CASE_TITLE, 'UTF-8');
    }

    public function permisos()
    {
        return $this->hasMany(UserPermiso::class);
    }

    public function avisos()
    {
        return $this->hasMany(Aviso::class, 'user_id');
    }

    protected static function booted()
    {
        static::created(function (User $user) {
            if ($user->role === 'abogado') {
                $user->otorgarPermisosPorDefecto();
            }
        });
    }

    public function puede(string $permiso): bool
    {
        if ($this->esCoordinadora()) {
            return true;
        }

        $grant = $this->relationLoaded('permisos')
            ? $this->permisos->firstWhere('permiso', $permiso)
            : $this->permisos()->where('permiso', $permiso)->first();

        return $grant instanceof UserPermiso && $grant->estaVigente();
    }

    public function exigir(string $permiso): void
    {
        abort_unless($this->puede($permiso), 403, 'No tienes permiso para esta acción.');
    }

    public function otorgarPermisosPorDefecto(?int $grantedBy = null): void
    {
        foreach (RhPermisos::porDefecto() as $clave) {
            $this->permisos()->updateOrCreate(
                ['permiso' => $clave],
                ['granted_by' => $grantedBy, 'expires_at' => null]
            );
        }
    }

    public function otorgarPermiso(string $clave, ?int $horas, ?int $grantedBy = null): UserPermiso
    {
        abort_unless(in_array($clave, RhPermisos::claves(), true), 422);

        return $this->permisos()->updateOrCreate(
            ['permiso' => $clave],
            [
                'granted_by' => $grantedBy,
                'expires_at' => $horas ? now()->addHours($horas) : null,
            ]
        );
    }

    public function sincronizarPermisos(array $claves, array $duraciones, ?int $grantedBy = null, array $horasCustom = []): void
    {
        $validas = array_values(array_intersect(RhPermisos::claves(), $claves));
        $this->permisos()->whereNotIn('permiso', $validas)->delete();

        foreach ($validas as $clave) {
            $horas = RhPermisos::resolverHoras(
                $duraciones[$clave] ?? 'permanente',
                $horasCustom[$clave] ?? null
            );
            $this->permisos()->updateOrCreate(
                ['permiso' => $clave],
                [
                    'granted_by' => $grantedBy,
                    'expires_at' => $horas ? now()->addHours($horas) : null,
                ]
            );
        }
    }

    public function permisosParaFormulario(): array
    {
        $mapa = [];
        foreach ($this->permisos as $grant) {
            if (!$grant->estaVigente()) {
                continue;
            }
            $mapa[$grant->permiso] = [
                'on' => true,
                'temporal' => $grant->esTemporal(),
                'vence' => $grant->expires_at ? $grant->expires_at->format('Y-m-d H:i') : null,
                'duracion' => $this->duracionDesdeVencimiento($grant->expires_at),
            ];
        }

        return $mapa;
    }

    private function duracionDesdeVencimiento($expiresAt): string
    {
        if ($expiresAt === null) {
            return 'permanente';
        }

        $horas = now()->diffInHours($expiresAt, false);
        if ($horas <= 1) {
            return '1';
        }
        if ($horas <= 3) {
            return '3';
        }
        if ($horas <= 5) {
            return '5';
        }

        return 'custom';
    }
}
