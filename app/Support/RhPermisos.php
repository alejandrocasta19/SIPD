<?php

namespace App\Support;

class RhPermisos
{
    public const VER_CASOS = 'ver_casos';
    public const REGISTRAR_CASOS = 'registrar_casos';
    public const EDITAR_CASOS = 'editar_casos';
    public const ELIMINAR_CASOS = 'eliminar_casos';
    public const VER_DOCUMENTOS = 'ver_documentos';
    public const EDITAR_DOCUMENTOS = 'editar_documentos';
    public const VER_ANEXOS = 'ver_anexos';
    public const SUBIR_ANEXOS = 'subir_anexos';
    public const ELIMINAR_ANEXOS = 'eliminar_anexos';
    public const VER_PLAZOS = 'ver_plazos';
    public const VER_RESOLUCIONES = 'ver_resoluciones';
    public const VER_REINCIDENCIAS = 'ver_reincidencias';
    public const VER_REPORTES = 'ver_reportes';
    public const EDITAR_PERFIL = 'editar_perfil';
    public const ELIMINAR_NOTIFICACIONES = 'eliminar_notificaciones';

    public static function catalogo(): array
    {
        return [
            'expedientes' => [
                'label' => 'Expedientes',
                'items' => [
                    self::VER_CASOS => 'Ver casos asignados',
                    self::REGISTRAR_CASOS => 'Registrar procesos',
                    self::EDITAR_CASOS => 'Editar procesos',
                    self::ELIMINAR_CASOS => 'Eliminar procesos',
                ],
            ],
            'documentos' => [
                'label' => 'Documentos oficiales',
                'items' => [
                    self::VER_DOCUMENTOS => 'Ver autos y actas',
                    self::EDITAR_DOCUMENTOS => 'Generar y editar documentos',
                ],
            ],
            'anexos' => [
                'label' => 'Anexos',
                'items' => [
                    self::VER_ANEXOS => 'Ver anexos escaneados',
                    self::SUBIR_ANEXOS => 'Subir anexos',
                    self::ELIMINAR_ANEXOS => 'Eliminar anexos',
                ],
            ],
            'cuenta' => [
                'label' => 'Cuenta',
                'items' => [
                    self::EDITAR_PERFIL => 'Editar perfil y contraseña',
                    self::ELIMINAR_NOTIFICACIONES => 'Borrar notificaciones',
                ],
            ],
            'control' => [
                'label' => 'Control y consulta',
                'items' => [
                    self::VER_PLAZOS => 'Ver plazos y términos',
                    self::VER_RESOLUCIONES => 'Ver resoluciones',
                    self::VER_REINCIDENCIAS => 'Ver reincidencias',
                    self::VER_REPORTES => 'Ver estadísticas y reportes',
                ],
            ],
        ];
    }

    public static function claves(): array
    {
        $claves = [];
        foreach (self::catalogo() as $grupo) {
            foreach (array_keys($grupo['items']) as $clave) {
                $claves[] = $clave;
            }
        }

        return $claves;
    }

    public static function etiqueta(string $clave): string
    {
        foreach (self::catalogo() as $grupo) {
            if (isset($grupo['items'][$clave])) {
                return $grupo['items'][$clave];
            }
        }

        return $clave;
    }

    public static function porDefecto(): array
    {
        return [
            self::VER_CASOS,
            self::REGISTRAR_CASOS,
            self::VER_DOCUMENTOS,
            self::EDITAR_DOCUMENTOS,
            self::VER_ANEXOS,
            self::SUBIR_ANEXOS,
            self::VER_PLAZOS,
            self::VER_RESOLUCIONES,
            self::VER_REINCIDENCIAS,
            self::VER_REPORTES,
        ];
    }

    public static function solicitables(): array
    {
        return [
            self::EDITAR_PERFIL => 'Editar perfil y contraseña',
            self::EDITAR_CASOS => 'Editar procesos',
            self::ELIMINAR_CASOS => 'Eliminar procesos',
            self::EDITAR_DOCUMENTOS => 'Generar y editar documentos',
            self::SUBIR_ANEXOS => 'Subir anexos',
            self::ELIMINAR_ANEXOS => 'Eliminar anexos',
            self::ELIMINAR_NOTIFICACIONES => 'Borrar notificaciones',
        ];
    }

    public static function duracionesHoras(): array
    {
        return [
            '1' => '1 hora',
            '3' => '3 horas',
            '5' => '5 horas',
            'custom' => 'Personalizada',
        ];
    }

    public static function resolverHoras($duracion, $personalizada = null): ?int
    {
        if ($duracion === null || $duracion === '' || $duracion === 'permanente') {
            return null;
        }

        if ($duracion === 'custom') {
            $horas = (int) $personalizada;
            return $horas > 0 ? min($horas, 168) : null;
        }

        $horas = (int) $duracion;
        return $horas > 0 ? $horas : null;
    }
}
