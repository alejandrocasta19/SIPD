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
        'activo',
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
        'activo' => 'boolean',
    ];

    public function procesos()
    {
        return $this->hasMany(ProcesoDisciplinario::class, 'user_id');
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EQUIPO = 'equipo';

    public const CARGOS_EQUIPO = [
        'Jefe de personal',
        'Asesor jurídico',
    ];

    public static function cargosEquipo(): array
    {
        return self::CARGOS_EQUIPO;
    }

    public static function normalizarRol(?string $role): string
    {
        return match ($role) {
            'admin', 'coordinadora' => self::ROLE_ADMIN,
            'equipo', 'abogado' => self::ROLE_EQUIPO,
            default => self::ROLE_EQUIPO,
        };
    }

    public function esCoordinadora(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function esEquipo(): bool
    {
        return $this->role === self::ROLE_EQUIPO;
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function etiquetaEquipo(): string
    {
        if ($this->esCoordinadora()) {
            return $this->cargo ?: 'Coordinadora de RH';
        }

        return $this->cargo ?: 'Equipo de RH';
    }

    public static function partesNombre(string $name): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
    }

    public static function titularParte(string $parte): string
    {
        return mb_convert_case(mb_strtolower($parte), MB_CASE_TITLE, 'UTF-8');
    }

    public static function primerNombreDe(string $name): string
    {
        $partes = self::partesNombre($name);

        return $partes ? self::titularParte($partes[0]) : '';
    }

    public static function primerApellidoDe(string $name): string
    {
        $partes = self::partesNombre($name);
        $n = count($partes);
        if ($n >= 4) {
            return self::titularParte($partes[2]);
        }
        if ($n >= 2) {
            return self::titularParte($partes[$n - 1]);
        }

        return '';
    }

    public function primerNombre(): string
    {
        return self::primerNombreDe((string) $this->name);
    }

    public function nombreCorto(): string
    {
        if ($this->esCoordinadora()) {
            return $this->cargo ?: (string) $this->name;
        }

        $nombre = $this->primerNombre();
        $apellido = self::primerApellidoDe((string) $this->name);

        return trim($nombre . ($apellido !== '' ? ' ' . $apellido : ''));
    }

    public static function nombreTitulado(string $name): string
    {
        return implode(' ', array_map([self::class, 'titularParte'], self::partesNombre($name)));
    }

    public function inicialesCortas(): string
    {
        $nombre = $this->primerNombre();
        $apellido = self::primerApellidoDe((string) $this->name);
        $ini = '';
        if ($nombre !== '') {
            $ini .= mb_strtoupper(mb_substr($nombre, 0, 1));
        }
        if ($apellido !== '') {
            $ini .= mb_strtoupper(mb_substr($apellido, 0, 1));
        }

        return $ini !== '' ? $ini : 'RH';
    }

    public static function slugParte(string $texto): string
    {
        $ascii = \Illuminate\Support\Str::ascii(mb_strtolower(trim($texto)));

        return preg_replace('/[^a-z]/', '', $ascii) ?: '';
    }

    public static function emailInstitucional(string $name): string
    {
        return self::slugParte(self::primerNombreDe($name))
            . self::slugParte(self::primerApellidoDe($name))
            . '@sipd.co';
    }

    public static function claveInstitucional(string $name): string
    {
        return self::slugParte(self::primerNombreDe($name)) . '123';
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
            if ($user->role === self::ROLE_EQUIPO) {
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
