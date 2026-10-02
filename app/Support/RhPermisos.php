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
    public const GENERAR_DOCUMENTOS = 'generar_documentos';
    public const EDITAR_GENERADOS = 'editar_generados';
    public const DESCARGAR_DOCUMENTOS = 'descargar_documentos';

    public const VER_ANEXOS = 'ver_anexos';
    public const SUBIR_ANEXOS = 'subir_anexos';
    public const EDITAR_ANEXOS = 'editar_anexos';
    public const DESCARGAR_ANEXOS = 'descargar_anexos';
    public const ELIMINAR_ANEXOS = 'eliminar_anexos';

    public const VER_PLAZOS = 'ver_plazos';
    public const VER_RESOLUCIONES = 'ver_resoluciones';
    public const VER_REINCIDENCIAS = 'ver_reincidencias';
    public const VER_REPORTES = 'ver_reportes';
    public const EXPORTAR_REPORTES = 'exportar_reportes';

    public const EDITAR_PERFIL = 'editar_perfil';
    public const ELIMINAR_NOTIFICACIONES = 'eliminar_notificaciones';

    public static function catalogo(): array
    {
        return [
            'registro' => [
                'label' => 'Nuevo Proceso',
                'icon' => 'fas fa-plus-circle',
                'desc' => 'Registrar queda permanente. Editar y eliminar se piden y llevan tiempo. Las tarjetas verdes son las modalidades que ve.',
                'lead' => 'Editar y eliminar son los permisos que esta persona debe pedir. Las tarjetas verdes son las modalidades que ve.',
                'items' => [
                    self::REGISTRAR_CASOS => 'Registrar procesos',
                    self::EDITAR_CASOS => 'Editar datos del expediente',
                    self::ELIMINAR_CASOS => 'Eliminar procesos',
                ],
                'hints' => [
                    self::REGISTRAR_CASOS => 'Entra a Nuevo proceso y deja un borrador. No genera ni descarga.',
                    self::EDITAR_CASOS => 'Cambiar conductor, cédula y demás datos del formulario.',
                    self::ELIMINAR_CASOS => 'Borrar el proceso por completo.',
                ],
            ],
            'documentos' => [
                'label' => 'Generar documentos',
                'icon' => 'fas fa-file-signature',
                'desc' => 'Ver, guardar borrador, generar y descargar van por separado. Continuar el borrador no es editar: la edición es después de generar.',
                'items' => [
                    self::VER_DOCUMENTOS => 'Ver formatos',
                    self::EDITAR_DOCUMENTOS => 'Guardar borrador',
                    self::GENERAR_DOCUMENTOS => 'Generar documento',
                    self::EDITAR_GENERADOS => 'Editar documento generado',
                    self::DESCARGAR_DOCUMENTOS => 'Descargar Word/PDF',
                ],
                'hints' => [
                    self::VER_DOCUMENTOS => 'Entra a Generar documentos y ve el formato.',
                    self::EDITAR_DOCUMENTOS => 'Continúa los cuadros amarillos sin generar.',
                    self::GENERAR_DOCUMENTOS => 'Marca el documento como generado. No descarga el archivo.',
                    self::EDITAR_GENERADOS => 'Cambia las zonas amarillas después de generar. No es continuar el borrador.',
                    self::DESCARGAR_DOCUMENTOS => 'Baja Word o PDF. Solo si necesita el archivo.',
                ],
            ],
            'casos' => [
                'label' => 'Mis Casos',
                'icon' => 'fas fa-search',
                'desc' => 'Consulta y gestión de expedientes asignados. Ver no da de alta ni borra.',
                'items' => [
                    self::VER_CASOS => 'Ver casos',
                    self::EDITAR_CASOS => 'Editar datos del expediente',
                    self::ELIMINAR_CASOS => 'Eliminar procesos',
                ],
                'hints' => [
                    self::VER_CASOS => 'Mis casos y el detalle del expediente.',
                    self::EDITAR_CASOS => 'Cambiar conductor, cédula y demás datos del formulario.',
                    self::ELIMINAR_CASOS => 'Borrar el proceso por completo.',
                ],
            ],
            'reincidencias' => [
                'label' => 'Reincidencias',
                'icon' => 'fas fa-history',
                'desc' => 'Historial disciplinario por cédula.',
                'items' => [
                    self::VER_REINCIDENCIAS => 'Ver reincidencias',
                ],
                'hints' => [
                    self::VER_REINCIDENCIAS => 'Historial por cédula.',
                ],
            ],
            'plazos' => [
                'label' => 'Plazos y términos',
                'icon' => 'far fa-clock',
                'desc' => 'Tablero de términos del proceso.',
                'items' => [
                    self::VER_PLAZOS => 'Ver plazos y términos',
                ],
                'hints' => [
                    self::VER_PLAZOS => 'Tablero de términos del proceso.',
                ],
            ],
            'anexos' => [
                'label' => 'Anexos escaneados',
                'icon' => 'fas fa-file-upload',
                'desc' => 'Ver el listado no permite subir, editar, bajar ni borrar. Cada acción se pide aparte.',
                'items' => [
                    self::VER_ANEXOS => 'Ver anexos',
                    self::SUBIR_ANEXOS => 'Subir anexos',
                    self::EDITAR_ANEXOS => 'Editar anexos',
                    self::DESCARGAR_ANEXOS => 'Descargar anexos',
                    self::ELIMINAR_ANEXOS => 'Eliminar anexos',
                ],
                'hints' => [
                    self::VER_ANEXOS => 'Consulta los archivos del expediente.',
                    self::SUBIR_ANEXOS => 'Cargar un archivo nuevo o el firmado.',
                    self::EDITAR_ANEXOS => 'Cambiar el tipo o reemplazar un archivo ya cargado.',
                    self::DESCARGAR_ANEXOS => 'Bajar el archivo adjunto.',
                    self::ELIMINAR_ANEXOS => 'Quitar un anexo del expediente.',
                ],
            ],
            'resoluciones' => [
                'label' => 'Resoluciones',
                'icon' => 'far fa-file-alt',
                'desc' => 'Consulta de resoluciones del expediente.',
                'items' => [
                    self::VER_RESOLUCIONES => 'Ver resoluciones',
                ],
                'hints' => [
                    self::VER_RESOLUCIONES => 'Consulta de resoluciones.',
                ],
            ],
            'reportes' => [
                'label' => 'Estadísticas / Reportes',
                'icon' => 'fas fa-chart-bar',
                'desc' => 'Ver el tablero no descarga el informe. Exportar se pide aparte.',
                'items' => [
                    self::VER_REPORTES => 'Ver estadísticas',
                    self::EXPORTAR_REPORTES => 'Exportar reportes',
                ],
                'hints' => [
                    self::VER_REPORTES => 'Ver el tablero. No descarga Excel, Word ni PDF.',
                    self::EXPORTAR_REPORTES => 'Descargar el informe en Excel, Word o PDF.',
                ],
            ],
            'cuenta' => [
                'label' => 'Cuenta',
                'icon' => 'fas fa-user-cog',
                'desc' => 'Datos personales y bandeja. No es un módulo del menú; se asigna aparte.',
                'items' => [
                    self::EDITAR_PERFIL => 'Editar perfil y contraseña',
                    self::ELIMINAR_NOTIFICACIONES => 'Borrar notificaciones',
                ],
                'hints' => [
                    self::EDITAR_PERFIL => 'Nombre, correo y contraseña.',
                    self::ELIMINAR_NOTIFICACIONES => 'Vaciar avisos de la bandeja.',
                ],
            ],
        ];
    }

    public static function requiere(): array
    {
        $mapa = [
            self::REGISTRAR_CASOS => [self::VER_CASOS],
            self::EDITAR_CASOS => [self::VER_CASOS],
            self::ELIMINAR_CASOS => [self::VER_CASOS],
            self::EDITAR_DOCUMENTOS => [self::VER_DOCUMENTOS],
            self::GENERAR_DOCUMENTOS => [self::VER_DOCUMENTOS],
            self::EDITAR_GENERADOS => [self::VER_DOCUMENTOS],
            self::DESCARGAR_DOCUMENTOS => [self::VER_DOCUMENTOS],
            self::SUBIR_ANEXOS => [self::VER_ANEXOS],
            self::EDITAR_ANEXOS => [self::VER_ANEXOS],
            self::DESCARGAR_ANEXOS => [self::VER_ANEXOS],
            self::ELIMINAR_ANEXOS => [self::VER_ANEXOS],
            self::EXPORTAR_REPORTES => [self::VER_REPORTES],
        ];

        foreach (array_keys(Modalidades::permisos()) as $clave) {
            $mapa[$clave] = [self::REGISTRAR_CASOS];
        }

        return $mapa;
    }

    public static function expandir(array $claves): array
    {
        $validas = array_values(array_intersect(self::claves(), $claves));
        $set = array_fill_keys($validas, true);
        $requiere = self::requiere();
        $changed = true;

        while ($changed) {
            $changed = false;
            foreach (array_keys($set) as $clave) {
                foreach ($requiere[$clave] ?? [] as $padre) {
                    if (!isset($set[$padre]) && in_array($padre, self::claves(), true)) {
                        $set[$padre] = true;
                        $changed = true;
                    }
                }
            }
        }

        return array_keys($set);
    }

    public static function claves(): array
    {
        $claves = [];
        foreach (self::catalogo() as $grupo) {
            foreach (array_keys($grupo['items']) as $clave) {
                $claves[] = $clave;
            }
        }

        return array_values(array_unique(array_merge($claves, array_keys(Modalidades::permisos()))));
    }

    public static function etiqueta(string $clave): string
    {
        foreach (self::catalogo() as $grupo) {
            if (isset($grupo['items'][$clave])) {
                return $grupo['items'][$clave];
            }
        }

        return Modalidades::permisos()[$clave] ?? $clave;
    }

    public static function hint(string $clave): string
    {
        foreach (self::catalogo() as $grupo) {
            if (isset($grupo['hints'][$clave])) {
                return $grupo['hints'][$clave];
            }
        }

        return '';
    }

    public static function grupoDe(string $clave): ?string
    {
        foreach (self::catalogo() as $grupo => $def) {
            if (isset($def['items'][$clave])) {
                return $grupo;
            }
        }

        if (isset(Modalidades::permisos()[$clave])) {
            return 'registro';
        }

        return null;
    }

    public static function esTemporalizable(string $clave): bool
    {
        if ($clave === self::EDITAR_DOCUMENTOS) {
            return false;
        }

        return strncmp($clave, 'editar_', 7) === 0 || strncmp($clave, 'eliminar_', 9) === 0;
    }

    public static function porDefecto(): array
    {
        return [
            self::VER_CASOS,
            self::REGISTRAR_CASOS,
            self::VER_DOCUMENTOS,
            self::EDITAR_DOCUMENTOS,
            self::GENERAR_DOCUMENTOS,
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
        $mapa = [];
        foreach (self::solicitablesAgrupados() as $grupo) {
            foreach ($grupo['items'] as $clave => $etiqueta) {
                $mapa[$clave] = $etiqueta;
            }
        }

        return $mapa;
    }

    public static function solicitablesAgrupados(): array
    {
        $acciones = [
            self::REGISTRAR_CASOS,
            self::EDITAR_CASOS,
            self::ELIMINAR_CASOS,
            self::EDITAR_DOCUMENTOS,
            self::GENERAR_DOCUMENTOS,
            self::EDITAR_GENERADOS,
            self::DESCARGAR_DOCUMENTOS,
            self::SUBIR_ANEXOS,
            self::EDITAR_ANEXOS,
            self::DESCARGAR_ANEXOS,
            self::ELIMINAR_ANEXOS,
            self::EXPORTAR_REPORTES,
            self::EDITAR_PERFIL,
            self::ELIMINAR_NOTIFICACIONES,
        ];

        $grupos = [];
        foreach (self::catalogo() as $grupo) {
            $items = [];
            foreach ($grupo['items'] as $clave => $etiqueta) {
                if (in_array($clave, $acciones, true)) {
                    $items[$clave] = $etiqueta;
                }
            }
            if ($items !== []) {
                $grupos[] = [
                    'label' => $grupo['label'],
                    'items' => $items,
                ];
            }
        }

        return $grupos;
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
