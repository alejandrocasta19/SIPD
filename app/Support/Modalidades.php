<?php

namespace App\Support;

use App\Models\User;

class Modalidades
{
    /**
     * Clave de permiso => nombre visible.
     *
     * @var array<string, string>
     */
    private const PERMISOS = [
        'modalidad_doble_yo' => 'Doble Yo',
        'modalidad_premium' => 'Premium',
        'modalidad_platino_express' => 'Platino Express',
        'modalidad_asistente_de_ventas' => 'Asistente de Ventas',
        'modalidad_monitoreo' => 'Monitoreo',
        'modalidad_call_center' => 'Call center',
        'modalidad_administrativos' => 'Administrativos',
        'modalidad_premium_pqr' => 'Premium PQR y correos',
        'modalidad_urbanos' => 'Urbanos',
        'modalidad_despacho' => 'Despacho',
        'modalidad_platino_express_pqr' => 'Platino Express PQR y correos',
        'modalidad_encomiendas' => 'Encomiendas',
        'modalidad_mixto' => 'Mixto',
        'modalidad_doble_yo_pqr' => 'Doble Yo PQR y correos',
        'modalidad_platino_jet' => 'Platino Jet',
        'modalidad_inspectores_viales' => 'Inspectores viales',
        'modalidad_estaciones' => 'Estaciones',
        'modalidad_terminal' => 'Terminal',
    ];

    /** @var list<string> */
    private const AREAS_ADMINISTRATIVAS = [
        'Recursos humanos',
        'Sistemas y marketing',
        'Jurídicas',
        'Recepción',
        'Control interno',
        'Tesorería',
        'Contabilidad',
        'Cartera',
        'Secretaría general',
        'Secretaría de gerencia',
        'Archivo',
    ];

    /** @var list<string> */
    private const ESTACIONES = [
        'Estación toma',
        'Estación principal',
        'Estación terminal',
        'Transporte',
    ];

    /**
     * Servicios en los que el trabajador opera un vehículo.
     * Las de oficina, PQR y despacho no llevan placa.
     *
     * @var list<string>
     */
    private const CON_PLACA = [
        'Doble Yo',
        'Premium',
        'Platino Express',
        'Urbanos',
        'Encomiendas',
        'Mixto',
        'Platino Jet',
        'Terminal',
    ];

    /**
     * Activas al crear la cuenta, según documentos/Distribución.xlsx.
     *
     * @var array<string, list<string>>
     */
    private const POR_CARGO = [
        'jefe de personal' => [
            'modalidad_doble_yo',
            'modalidad_premium',
            'modalidad_platino_express',
        ],
        'asesora juridica' => [
            'modalidad_asistente_de_ventas',
            'modalidad_call_center',
            'modalidad_administrativos',
            'modalidad_estaciones',
            'modalidad_premium_pqr',
            'modalidad_urbanos',
            'modalidad_despacho',
        ],
        'asesor juridico' => [
            'modalidad_platino_express_pqr',
            'modalidad_encomiendas',
            'modalidad_mixto',
            'modalidad_doble_yo_pqr',
            'modalidad_platino_jet',
            'modalidad_inspectores_viales',
            'modalidad_terminal',
        ],
    ];

    public static function permisos(): array
    {
        return self::PERMISOS;
    }

    public static function todas(): array
    {
        return array_values(self::PERMISOS);
    }

    public static function conPlaca(): array
    {
        return self::CON_PLACA;
    }

    public static function usaPlaca(?string $nombre): bool
    {
        return in_array(trim((string) $nombre), self::CON_PLACA, true);
    }

    public static function pideCargo(?string $seleccion): bool
    {
        $seleccion = trim((string) $seleccion);

        return in_array($seleccion, self::CON_CARGO, true);
    }

    public static function areasAdministrativas(): array
    {
        return self::AREAS_ADMINISTRATIVAS;
    }

    public static function estaciones(): array
    {
        return self::ESTACIONES;
    }

    /**
     * @return array{modalidad: ?string, cargo: ?string}|null
     */
    public static function interpretar(string $seleccion, ?string $cargoLibre = null): ?array
    {
        $seleccion = trim($seleccion);
        $cargoLibre = trim((string) $cargoLibre);
        if ($seleccion === '') {
            return ['modalidad' => null, 'cargo' => null];
        }
        if ($seleccion === 'Administrativos') {
            if (!in_array($cargoLibre, self::AREAS_ADMINISTRATIVAS, true)) {
                return null;
            }

            return ['modalidad' => 'Administrativos', 'cargo' => $cargoLibre];
        }
        if (in_array($seleccion, self::AREAS_ADMINISTRATIVAS, true)) {
            return ['modalidad' => 'Administrativos', 'cargo' => $seleccion];
        }
        if (in_array($seleccion, self::ESTACIONES, true)) {
            return [
                'modalidad' => $seleccion,
                'cargo' => $seleccion === 'Estación toma' && $cargoLibre !== '' ? $cargoLibre : null,
            ];
        }
        if (in_array($seleccion, self::ESTACIONES, true)
            || ($seleccion !== 'Administrativos' && $seleccion !== 'Estaciones' && in_array($seleccion, self::todas(), true))) {
            return [
                'modalidad' => $seleccion,
                'cargo' => $cargoLibre !== '' ? $cargoLibre : null,
            ];
    {
        $clave = self::claveDeSeleccion($seleccion);
        if ($clave === null) {
            return false;
        }
        if ($user->esCoordinadora()) {
            return true;
        }

        return $user->puede($clave);
    }

    public static function claveDeSeleccion(string $seleccion): ?string
    {
        $seleccion = trim($seleccion);
        if ($seleccion === 'Administrativos' || in_array($seleccion, self::AREAS_ADMINISTRATIVAS, true)) {
            return 'modalidad_administrativos';
        }
        if (in_array($seleccion, self::ESTACIONES, true)) {
            return 'modalidad_estaciones';
        }
        if ($seleccion === 'Terminal') {
            return 'modalidad_terminal';
        }
        $clave = array_search($seleccion, self::PERMISOS, true);

        return $clave === false || in_array($seleccion, ['Administrativos', 'Estaciones'], true) ? null : $clave;
    }

    public static function etiquetaCaso(?string $modalidad, ?string $cargo): string
    {
        $modalidad = trim((string) $modalidad);
        $cargo = trim((string) $cargo);
        if ($modalidad === '' && $cargo === '') {
            return '—';
        }
        if ($cargo !== '' && $cargo !== $modalidad) {
            return ($modalidad !== '' ? $modalidad . ' · ' : '') . $cargo;
        }

        return $modalidad !== '' ? $modalidad : $cargo;
    }

    public static function aplicarFiltro($query, string $valor): void
    {
        $valor = trim($valor);
        if ($valor === '') {
            return;
        }
        if (in_array($valor, self::AREAS_ADMINISTRATIVAS, true)) {
            $query->where(function ($inner) use ($valor) {
                $inner->where(function ($admin) use ($valor) {
                    $admin->where('modalidad', 'Administrativos')->where('cargo', $valor);
                })->orWhere('modalidad', $valor);
            });

            return;
        }
        $query->where('modalidad', $valor);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function opcionesFiltro(): array
    {
        $operacion = [];
        foreach (self::PERMISOS as $nombre) {
            if (!in_array($nombre, ['Administrativos', 'Estaciones', 'Terminal'], true)) {
                $operacion[] = $nombre;
            }
        }

        return [
            'Administrativos' => self::AREAS_ADMINISTRATIVAS,
            'Estaciones' => self::ESTACIONES,
            'Terminal' => ['Terminal'],
            'Operación' => $operacion,
        ];
    }

    /**
     * @return list<array{tipo: string, id: string, nombre: string, opciones: list<string>}>
     */
    public static function selector(User $user): array
    {
        $items = [];
        $grupos = [
            'administrativos' => ['nombre' => 'Administrativos', 'clave' => 'modalidad_administrativos', 'opciones' => self::AREAS_ADMINISTRATIVAS],
            'estaciones' => ['nombre' => 'Estaciones', 'clave' => 'modalidad_estaciones', 'opciones' => self::ESTACIONES],
            'terminal' => ['nombre' => 'Terminal', 'clave' => 'modalidad_terminal', 'opciones' => []],
        ];
        foreach ($grupos as $id => $grupo) {
            if (!$user->esCoordinadora() && !$user->puede($grupo['clave'])) {
                continue;
            }
            $items[] = [
                'tipo' => $grupo['opciones'] === [] ? 'directo' : 'grupo',
                'id' => $id,
                'nombre' => $grupo['nombre'],
                'opciones' => $grupo['opciones'],
            ];
        }
        foreach (self::PERMISOS as $clave => $nombre) {
            if (in_array($nombre, ['Administrativos', 'Estaciones', 'Terminal'], true)) {
                continue;
            }
            if (!$user->esCoordinadora() && !$user->puede($clave)) {
                continue;
            }
            $items[] = [
                'tipo' => 'directo',
                'id' => $clave,
                'nombre' => $nombre,
                'opciones' => [],
            ];
        }

        return $items;
    }

    public static function textoPlaca(?string $modalidad, ?string $placa): string
    {
        $placa = trim((string) $placa);
        if (trim((string) $modalidad) === '') {
            return $placa !== '' ? $placa : '—';
        }
        if (!self::usaPlaca($modalidad)) {
            return 'No aplica';
        }

        return $placa !== '' ? $placa : '—';
    }

            return 'N/A';
    {
        return self::POR_CARGO[self::claveCargo($cargo)] ?? [];
    }

    /**
     * @return list<array{nombre: string, activa: bool}>
     */
    public static function para(User $user): array
    {
        $permitidas = array_fill_keys(self::permitidas($user), true);

        return array_map(function (string $nombre) use ($permitidas) {
            return [
                'nombre' => $nombre,
                'activa' => isset($permitidas[$nombre]),
            ];
        }, self::todas());
    }

    public static function permite(User $user, string $nombre): bool
    {
        return in_array(trim($nombre), self::permitidas($user), true);
    }

    public static function permitidas(User $user): array
    {
        if ($user->esCoordinadora()) {
            return self::todas();
        }

        $user->loadMissing('permisos');
        $nombres = [];
        foreach (self::PERMISOS as $clave => $nombre) {
            if ($user->puede($clave)) {
                $nombres[] = $nombre;
            }
        }

        return $nombres;
    }

    public static function claveCargo(?string $cargo): string
    {
        $texto = mb_strtolower(trim((string) $cargo));
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $texto) ?? '';
    }
}
