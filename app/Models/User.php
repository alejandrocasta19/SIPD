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
        $this->unsetRelation('permisos');
    }

    public function otorgarPermiso(string $clave, ?int $horas, ?int $grantedBy = null): UserPermiso
    {
        abort_unless(in_array($clave, RhPermisos::claves(), true), 422);

        $expires = $horas ? now()->addHours($horas) : null;

        foreach (RhPermisos::expandir([$clave]) as $item) {
            if ($item === $clave) {
                continue;
            }
            if (!$this->puede($item)) {
                $this->permisos()->updateOrCreate(
                    ['permiso' => $item],
                    ['granted_by' => $grantedBy, 'expires_at' => $expires]
                );
            }
        }

        $grant = $this->permisos()->updateOrCreate(
            ['permiso' => $clave],
            [
                'granted_by' => $grantedBy,
                'expires_at' => $expires,
            ]
        );
        $this->unsetRelation('permisos');

        return $grant;
    }

    public function sincronizarPermisos(array $claves, array $duraciones, ?int $grantedBy = null, array $horasCustom = []): void
    {
        $validas = RhPermisos::expandir($claves);
        $this->permisos()->whereNotIn('permiso', $validas)->delete();

        foreach ($validas as $clave) {
            $grupo = RhPermisos::grupoDe($clave);
            $horas = RhPermisos::resolverHoras(
                $duraciones[$grupo] ?? $duraciones[$clave] ?? 'permanente',
                $horasCustom[$grupo] ?? $horasCustom[$clave] ?? null
            );
            $this->permisos()->updateOrCreate(
                ['permiso' => $clave],
                [
                    'granted_by' => $grantedBy,
                    'expires_at' => $horas ? now()->addHours($horas) : null,
                ]
            );
        }
        $this->unsetRelation('permisos');
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
            ];
        }

        return $mapa;
    }

    public function duracionesModuloParaFormulario(): array
    {
        $elegidos = [];
        foreach ($this->permisos as $grant) {
            if (!$grant->estaVigente()) {
                continue;
            }
            $grupo = RhPermisos::grupoDe($grant->permiso);
            if (!$grupo) {
                continue;
            }
            $actual = $elegidos[$grupo] ?? null;
            if (!$actual || ($grant->expires_at && (!$actual->expires_at || $grant->expires_at->lt($actual->expires_at)))) {
                $elegidos[$grupo] = $grant;
            }
        }

        $mapa = [];
        foreach (array_keys(RhPermisos::catalogo()) as $grupo) {
            $grant = $elegidos[$grupo] ?? null;
            $duracion = $grant ? $this->duracionDesdeVencimiento($grant->expires_at) : 'permanente';
            $mapa[$grupo] = [
                'duracion' => $duracion,
                'horas' => $duracion === 'custom' && $grant && $grant->expires_at
                    ? max(1, (int) now()->diffInHours($grant->expires_at, false))
                    : '',
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
