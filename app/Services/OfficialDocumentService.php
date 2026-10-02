<?php

namespace App\Services;

use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;
use ZipArchive;

class OfficialDocumentService
{
    /**
     * Nombres exactos de las plantillas oficiales ubicadas en /documentos.
     */
    private const TEMPLATES = [
        'comprobacion'  => '1.1 GA-FT-045 Formato de apertura Proceso Comprobacion.docx',
        'disciplinario' => '1.1 GA-FT-045 Formato de apertura Proceso Disciplinarios.docx',
        'acta'          => '2. Acta de cargos y descargos (grabacion).docx',
        'sancion'       => '3. sancion.docx',
        'llamado'       => '4. llamado de atencion.docx',
        'terminacion'   => '5. Terminacion por justas causas.docx',
        'archivo'       => 'Desición de Archivo.docx',
    ];

    /**
     * Definición exacta de etiquetas de bloques amarillos derivados de los XML reales.
     */
    private const BLOCKS = [
        'disciplinario' => [
            'Contexto de la queja o informe presentado',
            'Recuento de los hechos investigados',
            'Deber 1: Cumplimiento adecuado de funciones',
            'Deber 2: Diligencia y responsabilidad en el cargo',
            'Deber 3: Asistencia al trabajo',
            'Deber 4: Disciplina y buen comportamiento',
            'Deber 5: Veracidad en la información suministrada',
            'Mención de las pruebas presentadas con el informe',
        ],
        'comprobacion' => [
            'Origen del informe allegado a Recursos Humanos',
            'Encabezado de los hechos objeto de investigación',
            'Título del Hecho 1',
            'Detalle del Hecho 1',
            'Título del Hecho 2',
            'Detalle del Hecho 2',
            'Título del Hecho 3',
            'Detalle del Hecho 3',
            'Título del Hecho 4',
            'Detalle del Hecho 4',
            'Consecuencia operativa y sobrecarga en el área',
            'Análisis de presunto incumplimiento de obligaciones',
            'Fundamento institucional del servicio de Cootranshuila',
            'Obligación 1: Diligencia, eficiencia y responsabilidad',
            'Obligación 2: Cumplimiento de jornada y horario laboral',
            'Obligación 3: Relaciones respetuosas y colaboración',
            'Obligación 4: Atención adecuada a los usuarios',
            'Obligación 5: Acatamiento de instrucciones y operación',
            'Obligación 6: Protección de la imagen institucional',
            'Prueba 1: Informe suscrito por la Jefatura',
            'Prueba 2: Programación de turnos',
            'Prueba 3: Registros de asistencia y control de ingreso',
            'Prueba 4: Registros operativos del Área de Despachos',
            'Prueba 5: Declaraciones de trabajadores',
            'Prueba 6: Demás elementos probatorios incorporados',
        ],
        'acta' => [
            'Encabezado del informe de descargos y comparecencia',
            'Fundamento del informe de Control Interno',
            'Detalle de inconsistencias en comprobantes y Nequi',
            'Resultado de validación con extractos bancarios',
            'Cuantía de comprobantes adulterados e impacto',
            'Funciones de control interno omisas',
            'Omisión específica en la revisión de consignaciones',
            'Circunstancia específica de agencias revisadas',
            'Tipificación de incumplimiento y faltas al RIT',
            'Constancia de grabación de la diligencia',
        ],
    ];

    /**
     * Textos literales extraídos de los documentos base para servir como guía o placeholder en el UI.
     */
    private const DEFAULT_TEXTS = [
        'disciplinario' => [
            'De conformidad con la queja formal presentada por la señora PATRICIA LOBO, Consignataria Bogotá Sur, y de acuerdo con los informes recibidos, se tuvo conocimiento de los siguientes hechos que podrían constituir faltas disciplinarias, conforme al Reglamento Interno de Trabajo de Cootranshuila Ltda.:',
            '(RECUENTO DE LA QUEJA U INFORME ALLEGADO A LA OFICINA DE RECURSOS HUMANOS)',
            'Cumplimiento adecuado de funciones',
            'Diligencia y responsabilidad en el cargo',
            'Asistencia al trabajo',
            'Disciplina y buen comportamiento',
            'Veracidad en la información suministrada',
            '(MENSION DE LAS PRUEBAS PRESENTADAS CON EL INFORME O QUEJA)',
        ],
        'comprobacion' => [
            'De conformidad con el informe presentado por la señora Ingrid Yolima Rodríguez Mendieta, Jefe de Taquilla, de fecha 29 de junio de 2026, remitido al Área de Talento Humano, se tuvo conocimiento de una serie de situaciones que, presuntamente, se han venido presentando de manera reiterada durante el desempeño de sus funciones en el Área de Despachos, las cuales podrían estar afectando el ambiente laboral, la calidad del servicio prestado por la Cooperativa y el normal desarrollo de la operación.',
            'Del contenido del informe se desprenden, en principio, los siguientes hechos objeto de investigación:',
            '1. Presuntas dificultades en las relaciones interpersonales y el trabajo en equipo.',
            'Se informa que, de manera reiterada, usted ha mantenido una actitud poco colaborativa y presuntamente irrespetuosa frente a sus compañeros de trabajo...',
            '2. Presuntas deficiencias en la atención y servicio al cliente.',
            'Igualmente, se reporta que durante el desarrollo de sus funciones usted presuntamente no estaría brindando una atención acorde con los estándares de servicio...',
            '3. Presunto incumplimiento del horario laboral.',
            'Así mismo, se informó que el día 1 de junio de 2026 usted tenía asignado turno laboral a partir de las 2:00 p.m.; sin embargo, presuntamente se presentó a laborar hasta las 4:00 p.m...',
            '4. Presuntas dificultades en la adaptación al cargo y afectación de la operación.',
            'Finalmente, la Jefe de Taquilla manifiesta que, pese al tiempo de acompañamiento, orientación y supervisión brindados por la empresa, usted ha presentado una adaptación limitada...',
            'De igual manera, se indica que dicha situación ha obligado a la Jefe de Taquilla a asumir jornadas laborales adicionales...',
            'Los hechos anteriormente descritos, de comprobarse dentro del presente procedimiento, podrían evidenciar un presunto incumplimiento...',
            'Es importante señalar que la Cooperativa fundamenta su operación en el trabajo coordinado entre sus colaboradores y en la prestación de un servicio público de transporte con altos estándares de calidad...',
            'El deber de desempeñar las funciones asignadas con diligencia, eficiencia y responsabilidad.',
            'La obligación de cumplir oportunamente la jornada y los horarios de trabajo.',
            'El deber de mantener relaciones respetuosas y de colaboración con compañeros, superiores y demás trabajadores.',
            'La obligación de brindar una atención adecuada, respetuosa y oportuna a los usuarios de la Cooperativa.',
            'El deber de acatar las instrucciones impartidas por los superiores y contribuir al adecuado funcionamiento de la operación.',
            'La obligación de proteger los intereses, la imagen institucional y el buen nombre de Cootranshuila Ltda.',
            'Informe suscrito por la señora Ingrid Yolima Rodríguez Mendieta, Jefe de Taquilla, de fecha 29 de junio de 2026.',
            'Programación de turnos correspondiente al día 1 de junio de 2026.',
            'Registros de asistencia o control de ingreso, si existen.',
            'Registros operativos del Área de Despachos.',
            'Declaraciones de los trabajadores que puedan tener conocimiento de los hechos.',
            'Los demás documentos, registros y elementos probatorios que reposen o lleguen a incorporarse dentro de la presente actuación disciplinaria.',
        ],
        'acta' => [
            'el informe presentado por el área de Control Interno, derivado de las revisiones efectuadas los días 08 y 09 de abril de 2026 sobre el control de saldos, recaudos y consignaciones...',
            'Con fundamento en el informe presentado por el área de Control Interno, derivado de las revisiones efectuadas los días 08 y 09 de abril de 2026...',
            'De acuerdo con el informe, mediante revisión realizada el día 08 de abril de 2026 en la plataforma SILOG...',
            'Posteriormente, al efectuarse la validación con extractos bancarios de Bancolombia...',
            'La revisión permitió establecer que dichas inconsistencias se habrían presentado desde el día 11 de diciembre de 2025 hasta el 06 de abril de 2026...',
            'Según el procedimiento descrito en el mismo informe, dentro de las funciones asignadas al área de Control Interno se encontraba la revisión detallada...',
            'En razón a lo anterior, se evidencian presuntas omisiones en el cumplimiento de las funciones propias de su cargo como Auxiliar de Control Interno...',
            'De igual manera, llama la atención que dentro de sus funciones del mes de diciembre de 2025, se encontraba la revisión y aprobación de consignaciones...',
            'Los hechos anteriormente descritos, de ser comprobados, podrían constituir incumplimiento de las obligaciones laborales inherentes a su cargo...',
            '(Se deja constancia de que la totalidad de la intervención del trabajador queda registrada en el medio de grabación con su consentimiento y hace parte integral de la presente diligencia)',
        ]
    ];

    /**
     * Organización semántica de los índices de bloque por sección.
     */
    private const SECTIONS = [
        'disciplinario' => [
            [
                'titulo'  => 'I. Hechos que originan el proceso',
                'desc'    => 'Contexto y recuento de la queja o informe allegado.',
                'bloques' => [0, 1],
            ],
            [
                'titulo'  => 'II. Deberes y obligaciones laborales',
                'desc'    => 'Criterios y deberes aplicables según el Reglamento Interno de Trabajo.',
                'bloques' => [2, 3, 4, 5, 6],
            ],
            [
                'titulo'  => 'III. Elementos probatorios',
                'desc'    => 'Mención de las pruebas presentadas con el informe.',
                'bloques' => [7],
            ],
        ],
        'comprobacion' => [
            [
                'titulo'  => 'I. Informe que origina el proceso',
                'desc'    => 'Origen y contexto del informe presentado.',
                'bloques' => [0, 1],
            ],
            [
                'titulo'  => 'II. Hechos objeto de investigación',
                'desc'    => 'Títulos, detalles y consecuencias operativas de los hechos.',
                'bloques' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            ],
            [
                'titulo'  => 'III. Obligaciones laborales relacionadas',
                'desc'    => 'Deberes que se presumen afectados.',
                'bloques' => [13, 14, 15, 16, 17, 18],
            ],
            [
                'titulo'  => 'IV. Elementos probatorios',
                'desc'    => 'Pruebas y soportes documentales.',
                'bloques' => [19, 20, 21, 22, 23, 24],
            ],
        ],
        'acta' => [
            [
                'titulo'  => 'I. Encabezado de la diligencia',
                'desc'    => 'Comparecencia del trabajador y contexto general.',
                'bloques' => [0],
            ],
            [
                'titulo'  => 'II. Fundamentos e inconsistencias',
                'desc'    => 'Detalle del informe de Control Interno, hallazgos y cuantía.',
                'bloques' => [1, 2, 3, 4],
            ],
            [
                'titulo'  => 'III. Imputación de omisiones y tipificación',
                'desc'    => 'Funciones omitidas, análisis de responsabilidad y falta disciplinaria.',
                'bloques' => [5, 6, 7, 8],
            ],
            [
                'titulo'  => 'IV. Constancia de grabación',
                'desc'    => 'Registro y consentimiento del trabajador en medio de grabación.',
                'bloques' => [9],
            ],
        ],
    ];

    // ─── Métodos Públicos ──────────────────────────────────────────

    public function blockDefinitions(string $type): array
    {
        abort_unless(isset(self::TEMPLATES[$type]), 404, 'Tipo de documento no válido');
        if (isset(self::BLOCKS[$type])) {
        return self::BLOCKS[$type];
        }

        return $this->detectYellowBlocks($type);
    }

    public function fieldSections(string $type): array
    {
        if (isset(self::SECTIONS[$type])) {
        return self::SECTIONS[$type];
        }

        $n = count($this->blockDefinitions($type));
        return [[
            'titulo' => 'Campos editables del formato',
            'desc' => 'Las zonas amarillas son editables. Las que no se rellenen no se generan en el documento.',
            'bloques' => range(0, max(0, $n - 1)),
        ]];
    }

    public function defaultTexts(string $type): array
    {
        if (isset(self::DEFAULT_TEXTS[$type])) {
            return self::DEFAULT_TEXTS[$type];
        }

        return $this->detectYellowBlocks($type);
    }

    public function requiresGerentePrint(string $type): bool
    {
        return $type === 'terminacion';
    }

    public function templateFilename(string $type): ?string
    {
        return self::TEMPLATES[$type] ?? null;
    }


    public function downloadOfficial(
        ProcesoDisciplinario $proceso,
        string $template,
        string $filename
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $output = $this->buildDocx($proceso, $template);

        return response()->download($output, $filename . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function downloadOfficialPdf(
        ProcesoDisciplinario $proceso,
        string $template,
        string $filename
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $docxPath = $this->buildDocx($proceso, $template, true);
        $pdfPath  = $this->convertDocxToPdf($docxPath);

        if ($pdfPath !== null && file_exists($pdfPath)) {
            @unlink($docxPath);
            return response()->download($pdfPath, $filename . '.pdf', [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        }

        // fallback to docx if PDF generation fails
        return response()->download($docxPath, $filename . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function previewPdf(ProcesoDisciplinario $proceso, string $template)
    {
        $docxPath = $this->buildDocx($proceso, $template, false);
        $pdfPath  = $this->convertDocxToPdf($docxPath);

        if ($pdfPath !== null && file_exists($pdfPath)) {
            @unlink($docxPath);
            return response()->file($pdfPath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview.pdf"',
            ])->deleteFileAfterSend(true);
        }

        @unlink($pdfPath ?: '');
        return response()->download($docxPath, 'preview.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function getInteractiveDocumentHtml(string $tipo, array $currentValues = [], ?ProcesoDisciplinario $proceso = null, ?string $clauseMode = null): string
    {
        $filename = self::TEMPLATES[$tipo] ?? null;
        if (!$filename) return "<p>Error: Tipo de documento inválido.</p>";

        $source = base_path('documentos/' . $filename);
        if (!is_file($source)) {
            $source = storage_path('app/plantillas/' . $filename);
        }
        if (!is_file($source)) {
            return "<p>Error: Plantilla oficial '{$filename}' no encontrada.</p>";
        }

        // Usamos cache para no convertir a HTML en cada recarga
        $fileHash = md5_file($source);
        $cacheKey = "sipd_interactive_doc_{$tipo}_{$fileHash}_v10";

        $html = Cache::rememberForever($cacheKey, function () use ($source, $tipo) {
            return $this->generateInteractiveHtmlFromDocx($source, $tipo);
        });

        // Reemplazar los placeholders (o values vacíos) con los valores reales diligenciados
        foreach ($currentValues as $index => $value) {
            if (trim((string) $value) !== '') {
                // El textarea tiene el name: name="yellow_blocks_X[Y]"... >placeholder</textarea>
                // Para inyectar el valor actual de forma segura sin romper el HTML usando regex:
                $search = '/(name="yellow_blocks_' . preg_quote($tipo, '/') . '\[' . $index . '\]"[^>]*>)/';
                $html = preg_replace($search, '${1}' . htmlspecialchars($value, ENT_QUOTES), $html);
            }
        }

        $html = $this->applyOptionalClauseState($html, $tipo, $this->clauseModeFor($tipo, $proceso, $clauseMode));

        return $this->fillHeaderInHtml($html, ProcesoDisciplinario::datosEncabezadoDocumento($proceso));
    }

    private function generateInteractiveHtmlFromDocx(string $path, string $tipo): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        ob_start();
        $writer->save('php://output');
        $html = ob_get_clean();

        preg_match('/<body[^>]*>(.*?)<\/body>/s', $html, $m);
        if (isset($m[1])) {
            $html = $m[1];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $this->compactPhpWordLayout($dom, $xpath);
        
        $yellowIndex = 0;
        $defaults = $this->defaultTexts($tipo);
        $containers = $xpath->query('//p|//td|//th|//li|//h1|//h2|//h3|//h4|//h5|//h6');
        
        foreach ($containers as $p) {
            if (!($p instanceof \DOMElement)) {
                continue;
            }

            if ($p->tagName === 'p' && preg_match('/^_{10,}$/', trim($p->textContent))) {
                $p->setAttribute('class', 'sig-zone');
                $p->setAttribute('style', 'text-align:center; min-height:60px; color:#94a3b8; font-size:12px; cursor:pointer;');
                while ($p->firstChild) {
                    $p->removeChild($p->firstChild);
                }
                $txt = $dom->createElement('span', '(Firma digital — usa el botón Subir firma, debajo del documento)');
                $p->appendChild($txt);
            }

            $yellowSpans = $xpath->query('.//span', $p);
            $matched = [];
            foreach ($yellowSpans as $span) {
                if ($span instanceof \DOMElement && $this->styleLooksYellow($span->getAttribute('style'))) {
                    $matched[] = $span;
                }
            }

            if ($matched === [] && $this->styleLooksYellow($p->getAttribute('style'))) {
                $matched[] = $p;
            }

            if ($matched === []) {
                continue;
            }
            
            $originalText = '';
            foreach ($matched as $span) {
                $originalText .= $span->textContent;
            }
            $originalText = trim($originalText);
            $placeholder = $originalText !== '' ? $originalText : ($defaults[$yellowIndex] ?? '');

            $firstSpan = $matched[0];
            $inline = $this->yellowFieldIsInline($p, $matched) && mb_strlen($placeholder) <= 22;
            $textarea = $this->makeYellowTextarea($dom, $tipo, $yellowIndex, $placeholder, $inline);

            if ($firstSpan === $p) {
                while ($p->firstChild) {
                    $p->removeChild($p->firstChild);
                }
                $p->appendChild($textarea);
            } else {
            $firstSpan->parentNode->insertBefore($textarea, $firstSpan);
                foreach ($matched as $span) {
                    if ($span->parentNode) {
                $span->parentNode->removeChild($span);
                    }
                }
            }
            $yellowIndex++;
        }

        $expected = count($this->blockDefinitions($tipo));
        if ($yellowIndex < $expected) {
            $extra = $dom->createElement('div');
            $extra->setAttribute('style', 'margin-top:24px;padding:16px;border:1px dashed #fbbf24;background:#fffbeb;');
            $note = $dom->createElement('p', 'Campos amarillos adicionales del formato (en el mismo orden del documento):');
            $note->setAttribute('style', 'font-size:12px;font-weight:700;color:#92400e;margin:0 0 10px;');
            $extra->appendChild($note);
            for ($i = $yellowIndex; $i < $expected; $i++) {
                $placeholder = $defaults[$i] ?? ('Campo editable ' . ($i + 1));
                $extra->appendChild($this->makeYellowTextarea($dom, $tipo, $i, $placeholder));
            }
            $dom->appendChild($extra);
        }

        $this->wrapCyanOptionalClauses($dom, $xpath, $tipo);
        $this->compactPhpWordLayout($dom, $xpath);
        $this->prepareHeaderFieldsInHtml($dom, $xpath);
        $html = $dom->saveHTML();
        return preg_replace('/^<\?xml[^>]*>/', '', (string) $html) ?: $html;
    }

    private function compactPhpWordLayout(DOMDocument $dom, DOMXPath $xpath): void
    {
        foreach ($xpath->query('//*[@style]') as $el) {
            if ($el instanceof \DOMElement) {
                $el->setAttribute('style', $this->normalizeWordCss($el->getAttribute('style')));
            }
        }

        foreach ($xpath->query('//p') as $p) {
            if (!($p instanceof \DOMElement)) {
                continue;
            }
            $text = trim(str_replace("\xc2\xa0", ' ', $p->textContent));
            $hasWidget = $xpath->query('.//textarea|.//img|.//table', $p)->length > 0;
            if ($text === '' && !$hasWidget) {
                $p->setAttribute('style', trim($p->getAttribute('style') . ';margin:0.12em 0;line-height:0.3;min-height:0;font-size:1px;'));
            }
        }

        foreach ($xpath->query('//img') as $img) {
            if (!($img instanceof \DOMElement)) {
                continue;
            }
            $img->setAttribute('style', trim($img->getAttribute('style') . ';max-width:100%;height:auto;max-height:90px;'));
        }
    }

    private function normalizeWordCss(string $style): string
    {
        return (string) preg_replace_callback(
            '/(margin(?:-left|-right|-top|-bottom)?|padding(?:-left|-right|-top|-bottom)?|text-indent|width|height|min-height|max-height)\s*:\s*(-?\d+(?:\.\d+)?)(in|pt|px|cm|mm)/i',
            function (array $m) {
                $prop = strtolower($m[1]);
                $val = (float) $m[2];
                $unit = strtolower($m[3]);

                if ($unit === 'in' && abs($val) > 3) {
                    $val = $val / 1440;
                }

                $isBox = str_starts_with($prop, 'margin') || str_starts_with($prop, 'padding') || $prop === 'text-indent';
                if ($isBox) {
                    if ($unit === 'in' && abs($val) > 1.25) {
                        $val = $val >= 0 ? 0.45 : -0.45;
                    } elseif ($unit === 'pt' && abs($val) > 36) {
                        $val = $val >= 0 ? 10 : -10;
                    } elseif ($unit === 'px' && abs($val) > 64) {
                        $val = $val >= 0 ? 18 : -18;
                    }
                }

                if (in_array($prop, ['height', 'min-height'], true) && $unit === 'in' && $val > 2) {
                    return $prop . ': auto';
                }

                $fmt = rtrim(rtrim(sprintf('%.4f', $val), '0'), '.');
                return $m[1] . ': ' . $fmt . $unit;
            },
            $style
        );
    }

    private function yellowFieldIsInline(\DOMElement $container, array $matched): bool
    {
        foreach ($container->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                foreach ($matched as $span) {
                    if ($child === $span || $child->isSameNode($span) || ($span instanceof \DOMNode && $span->parentNode === $child)) {
                        continue 2;
                    }
                }
            }
            $text = trim(str_replace("\xc2\xa0", ' ', $child->textContent ?? ''));
            if ($text !== '') {
                return true;
            }
        }

        return false;
    }

    private function styleLooksYellow(string $style): bool
    {
        $style = strtolower($style);
        return str_contains($style, 'yellow')
            || str_contains($style, '#ffff00')
            || str_contains($style, 'rgb(255, 255, 0)')
            || str_contains($style, 'rgb(255,255,0)');
    }

    private function styleLooksCyan(string $style): bool
    {
        $style = strtolower($style);
        return str_contains($style, 'cyan')
            || str_contains($style, 'aqua')
            || str_contains($style, '#00ffff')
            || str_contains($style, '#0ff;')
            || str_contains($style, 'rgb(0, 255, 255)')
            || str_contains($style, 'rgb(0,255,255)');
    }

    public static function normalizeClauseMode(?string $mode): string
    {
        return in_array((string) $mode, ['no_presento', 'extemporaneo'], true)
            ? (string) $mode
            : 'omit';
    }

    private function clauseModeFor(string $tipo, ?ProcesoDisciplinario $proceso, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return self::normalizeClauseMode($override);
        }

        if ($proceso) {
            return self::normalizeClauseMode(
                $proceso->datos_oficiales['optional_clauses'][$tipo]['descargos_fuera_de_termino'] ?? null
            );
        }

        return 'omit';
    }

    private function applyOptionalClauseState(string $html, string $tipo, string $mode): string
    {
        if (!str_contains($html, 'CLAUSE_MODE_TOKEN')) {
            return $html;
        }

        return str_replace('CLAUSE_MODE_TOKEN', self::normalizeClauseMode($mode), $html);
    }

    private function elementLooksCyan(\DOMElement $el, DOMXPath $xpath): bool
    {
        if ($this->styleLooksCyan($el->getAttribute('style'))) {
            return true;
        }

        foreach ($el->getElementsByTagName('*') as $child) {
            if ($child instanceof \DOMElement && $this->styleLooksCyan($child->getAttribute('style'))) {
                return true;
            }
        }

        return false;
    }

    private function isOptionalDescargosParagraph(\DOMElement $p, DOMXPath $xpath): bool
    {
        if ($this->elementLooksCyan($p, $xpath)) {
            return true;
        }

        $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $p->textContent)) ?? ''));

        return (str_contains($text, 'se deja constancia') && str_contains($text, 'vencido'))
            || str_contains($text, 'manera extempor');
    }

    private function isHtmlSpacerParagraph(\DOMElement $p): bool
    {
        $text = trim(str_replace("\xc2\xa0", ' ', $p->textContent));

        return $text === '';
    }

    private function wrapCyanOptionalClauses(DOMDocument $dom, DOMXPath $xpath, string $tipo): void
    {
        if ($tipo !== 'sancion') {
            return;
        }

        $paragraphs = [];
        foreach ($xpath->query('//p') as $p) {
            if ($p instanceof \DOMElement) {
                $paragraphs[] = $p;
            }
        }

        $i = 0;
        $n = count($paragraphs);
        while ($i < $n) {
            if (!$this->isOptionalDescargosParagraph($paragraphs[$i], $xpath)) {
                $i++;
                continue;
            }

            $group = [$paragraphs[$i]];
            $j = $i + 1;
            while ($j < $n) {
                if ($this->isOptionalDescargosParagraph($paragraphs[$j], $xpath)) {
                    $group[] = $paragraphs[$j];
                    $j++;
                    continue;
                }
                if ($this->isHtmlSpacerParagraph($paragraphs[$j])
                    && ($j + 1) < $n
                    && $this->isOptionalDescargosParagraph($paragraphs[$j + 1], $xpath)) {
                    $group[] = $paragraphs[$j];
                    $j++;
                    continue;
                }
                break;
            }

            $this->wrapOptionalClauseGroup($dom, $xpath, $group, $tipo);
            $i = $j;
        }
    }

    private function wrapOptionalClauseGroup(DOMDocument $dom, DOMXPath $xpath, array $group, string $tipo): void
    {
        if ($group === []) {
            return;
        }

        $first = $group[0];
        $parent = $first->parentNode;
        if (!$parent) {
            return;
        }

        $wrapper = $dom->createElement('div');
        $wrapper->setAttribute('class', 'doc-optional-clause');
        $wrapper->setAttribute('data-clause', 'descargos_fuera_de_termino');
        $wrapper->setAttribute('data-mode', 'CLAUSE_MODE_TOKEN');

        $body = $dom->createElement('div');
        $body->setAttribute('class', 'doc-optional-body');

        $parent->insertBefore($wrapper, $first);
        $wrapper->appendChild($this->makeOptionalClauseBar($dom, $tipo));
        $wrapper->appendChild($body);

        $partIndex = 0;
        foreach ($group as $p) {
            if ($this->isOptionalDescargosParagraph($p, $xpath)) {
                $p->setAttribute('data-optional-part', $partIndex === 0 ? 'vencido' : 'extemporaneo');
                $partIndex++;
            }
            $body->appendChild($p);
        }
    }

    private function makeOptionalClauseBar(DOMDocument $dom, string $tipo): \DOMElement
    {
        $bar = $dom->createElement('div');
        $bar->setAttribute('class', 'doc-optional-bar');

        $hidden = $dom->createElement('input');
        $hidden->setAttribute('type', 'hidden');
        $hidden->setAttribute('name', "optional_clauses[{$tipo}][descargos_fuera_de_termino]");
        $hidden->setAttribute('value', 'CLAUSE_MODE_TOKEN');
        $hidden->setAttribute('class', 'js-optional-value');
        $bar->appendChild($hidden);

        $toggle = $dom->createElement('div');
        $toggle->setAttribute('class', 'doc-optional-toggle');
        $checkId = 'optional-include-' . $tipo . '-descargos_fuera_de_termino';
        $check = $dom->createElement('input');
        $check->setAttribute('type', 'checkbox');
        $check->setAttribute('id', $checkId);
        $check->setAttribute('class', 'js-optional-include');
        $toggle->appendChild($check);
        $title = $dom->createElement('label');
        $title->setAttribute('for', $checkId);
        $title->appendChild($dom->createTextNode('Incluir constancia de descargos fuera de término '));
        $em = $dom->createElement('em');
        $em->appendChild($dom->createTextNode('(opcional)'));
        $title->appendChild($em);
        $toggle->appendChild($title);
        $bar->appendChild($toggle);

        $hint = $dom->createElement('p');
        $hint->setAttribute('class', 'doc-optional-hint');
        $hint->appendChild($dom->createTextNode('Úsalo si no presentaron descargos o si los entregaron después del plazo.'));
        $bar->appendChild($hint);

        $modes = $dom->createElement('div');
        $modes->setAttribute('class', 'doc-optional-modes');
        foreach ([
            'no_presento' => 'No los presentaron',
            'extemporaneo' => 'Los presentaron tarde',
        ] as $value => $label) {
            $opt = $dom->createElement('div');
            $opt->setAttribute('class', 'doc-optional-choice');
            $radioId = 'optional-mode-' . $tipo . '-' . $value;
            $radio = $dom->createElement('input');
            $radio->setAttribute('type', 'radio');
            $radio->setAttribute('id', $radioId);
            $radio->setAttribute('class', 'js-optional-mode');
            $radio->setAttribute('name', 'optional_mode_' . $tipo . '_descargos_fuera_de_termino');
            $radio->setAttribute('value', $value);
            $radio->setAttribute('data-optional-choice', $value);
            $opt->appendChild($radio);
            $lab = $dom->createElement('label');
            $lab->setAttribute('for', $radioId);
            $lab->appendChild($dom->createTextNode($label));
            $opt->appendChild($lab);
            $modes->appendChild($opt);
        }
        $bar->appendChild($modes);

        return $bar;
    }

    private function makeYellowTextarea(DOMDocument $dom, string $tipo, int $index, string $placeholder, bool $inline = false): \DOMElement
    {
        $len = mb_strlen($placeholder);
        $textarea = $dom->createElement('textarea');
        $textarea->setAttribute('name', "yellow_blocks_{$tipo}[{$index}]");
        $textarea->setAttribute('data-index', (string) $index);
        $textarea->setAttribute('placeholder', htmlspecialchars($placeholder));
        if ($inline) {
            $ch = max(10, $len + 4);
            $textarea->setAttribute('class', "doc-interactive-field js-block-{$tipo} js-inline");
            $textarea->setAttribute('rows', '1');
            $textarea->setAttribute('style', "display:inline-block;min-width:{$ch}ch;width:{$ch}ch;max-width:100%;vertical-align:baseline;height:2em;min-height:2em;overflow:hidden;resize:none;font-family:inherit;font-size:inherit;line-height:1.35;padding:2px 8px;border:1.5px dashed #fbbf24;border-radius:4px;box-sizing:content-box;background:#fffbeb;outline:none;margin:0 4px;");
        } else {
            $textarea->setAttribute('class', "doc-interactive-field js-block-{$tipo} js-autosize");
            $textarea->setAttribute('rows', $len > 160 ? '4' : ($len > 70 ? '3' : '2'));
            $textarea->setAttribute('style', 'display:block;width:100%;min-height:2.1em;max-height:none;overflow:auto;resize:vertical;font-family:inherit;font-size:inherit;line-height:1.4;padding:6px 8px;border:1.5px dashed #fbbf24;border-radius:4px;box-sizing:border-box;background:#fffbeb;outline:none;margin:6px 0;');
        }
        return $textarea;
    }

    // ─── Métodos Privados ──────────────────────────────────────────
    /**
     * Construye un DOCX temporal a partir de la plantilla original sin alterar la fuente oficial.
     */
    private function buildDocx(ProcesoDisciplinario $proceso, string $template, bool $strictValidation = true): string
    {
        abort_unless(isset(self::TEMPLATES[$template]), 404, 'Tipo de documento no válido');

        $blocks = $this->normalizedYellowBlocks(
            $proceso->datos_oficiales['yellow_blocks'][$template] ?? [],
            $this->blockDefinitions($template)
        );

        // Buscar plantilla primaria en /documentos, fallback a storage/app/plantillas/
        $filename = self::TEMPLATES[$template];
        $source   = base_path('documentos/' . $filename);
        if (!is_file($source)) {
            $source = storage_path('app/plantillas/' . $filename);
        }

        abort_unless(is_file($source), 500, "No se encontró la plantilla oficial '{$filename}' en /documentos.");

        // Copiar a un archivo temporal único sin tocar el original
        $output = tempnam(storage_path('app'), 'sipd-doc-');
        copy($source, $output);

        $zip = new ZipArchive();
        abort_unless($zip->open($output) === true, 500, 'No se pudo abrir el archivo DOCX temporal.');

        $entryName   = 'word/document.xml';
        $originalXml = $zip->getFromName($entryName);
        $updatedXml  = $this->replaceYellowBlocks($originalXml, $blocks);
        $updatedXml  = $this->applyOptionalCyanClauses(
            $updatedXml,
            $template,
            $this->clauseModeFor($template, $proceso)
        );
        $updatedXml  = $this->fillHeaderInXml($updatedXml, ProcesoDisciplinario::datosEncabezadoDocumento($proceso));

        $zip->deleteName($entryName);
        $zip->addFromString($entryName, $updatedXml);
        $zip->close();

        return $output;
    }

    private function normalizedYellowBlocks(array $blocks, array $definitions): array
    {
        $out = [];
        foreach (array_keys($definitions) as $index) {
            $out[$index] = trim((string) ($blocks[$index] ?? ''));
        }

        return $out;
    }

    /**
     * Reemplaza los bloques resaltados en amarillo en el XML de Word.
     * Los vacíos se omiten; el texto fijo no variable permanece inalterado.
     */
    private function replaceYellowBlocks(string $xml, array $blocks): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $before = $this->fixedTextSignature($xpath);

        $paragraphs = [];
        foreach ($xpath->query('//w:p') as $paragraph) {
            if ($paragraph instanceof \DOMElement) {
                $paragraphs[] = $paragraph;
            }
        }

        $yellowIndex = 0;
        $removeParagraphs = [];
        $removeRuns = [];
        $strippedParagraphs = [];

        foreach ($paragraphs as $paragraph) {
            $runs = [];
            foreach ($xpath->query('.//w:r[w:rPr/w:highlight[@w:val="yellow"]]', $paragraph) as $run) {
                if ($run instanceof \DOMElement) {
                    $runs[] = $run;
                }
            }
            if ($runs === []) {
                continue;
            }

            $value = trim((string) ($blocks[$yellowIndex] ?? ''));
            if ($value === '') {
                if ($this->paragraphIsOnlyYellow($paragraph, $xpath)) {
                    $removeParagraphs[] = $paragraph;
                } else {
                    foreach ($runs as $run) {
                        $removeRuns[] = $run;
                    }
                    $strippedParagraphs[] = $paragraph;
                }
                $yellowIndex++;
                continue;
            }

            $firstText = null;
            foreach ($runs as $run) {
                $texts = $xpath->query('.//w:t', $run);
                foreach ($texts as $text) {
                    if (!($text instanceof \DOMText || $text instanceof \DOMElement)) {
                        continue;
                    }
                    /** @var \DOMElement $text */
                    if ($firstText === null) {
                        $firstText = $text;
                        $text->nodeValue = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
                    } else {
                        $text->nodeValue = '';
                    }
                }
            }
            $yellowIndex++;
        }

        foreach ($removeRuns as $run) {
            $run->parentNode?->removeChild($run);
        }

        foreach ($strippedParagraphs as $paragraph) {
            if ($paragraph->parentNode && $this->isEmptyWordParagraph($paragraph, $xpath)) {
                $removeParagraphs[] = $paragraph;
            }
        }

        $extraEmpty = [];
        foreach ($removeParagraphs as $paragraph) {
            $next = $this->nextWordParagraph($paragraph);
            if ($next && $this->isEmptyWordParagraph($next, $xpath)) {
                $extraEmpty[] = $next;
            }
        }

        $seen = [];
        foreach (array_merge($removeParagraphs, $extraEmpty) as $node) {
            $id = spl_object_id($node);
            if (isset($seen[$id]) || !$node->parentNode) {
                continue;
            }
            $seen[$id] = true;
            $node->parentNode->removeChild($node);
        }

        if ($yellowIndex !== count($blocks)) {
            throw new \RuntimeException("La plantilla contiene {$yellowIndex} bloques amarillos, pero se esperaban " . count($blocks) . ".");
        }

        $after = $this->fixedTextSignature($xpath);
        if ($before !== $after) {
            throw new \RuntimeException('La generación alteraría texto fijo no variable de la plantilla oficial.');
        }

        return $document->saveXML();
    }

    private function applyOptionalCyanClauses(string $xml, string $template, string $mode): string
    {
        if ($template !== 'sancion') {
            return $xml;
        }

        $mode = self::normalizeClauseMode($mode);
        if ($mode === 'extemporaneo') {
            return $xml;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        if (!$document->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return $xml;
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $cyan = [];
        foreach ($xpath->query('//w:p') as $paragraph) {
            if (!($paragraph instanceof \DOMElement)) {
                continue;
            }
            if ($xpath->query('.//w:r[w:rPr/w:highlight[@w:val="cyan"]]', $paragraph)->length === 0) {
                continue;
            }
            $cyan[] = $paragraph;
        }

        $targets = $mode === 'omit' ? $cyan : array_slice($cyan, 1);
        $remove = [];
        foreach ($targets as $paragraph) {
            $remove[] = $paragraph;
            $next = $this->nextWordParagraph($paragraph);
            if ($next && $this->isEmptyWordParagraph($next, $xpath)) {
                $remove[] = $next;
            }
        }

        foreach ($remove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return $document->saveXML() ?: $xml;
    }

    private function nextWordParagraph(\DOMElement $paragraph): ?\DOMElement
    {
        $node = $paragraph->nextSibling;
        while ($node) {
            if ($node instanceof \DOMElement && preg_match('/(?:^|:)p$/', $node->nodeName)) {
                return $node;
            }
            $node = $node->nextSibling;
        }

        return null;
    }

    private function isEmptyWordParagraph(\DOMElement $paragraph, DOMXPath $xpath): bool
    {
        $text = '';
        foreach ($xpath->query('.//w:t', $paragraph) as $t) {
            $text .= $t->nodeValue;
        }

        return trim(str_replace("\xc2\xa0", ' ', $text)) === '';
    }

    private function paragraphIsOnlyYellow(\DOMElement $paragraph, DOMXPath $xpath): bool
    {
        $hasYellow = false;

        foreach ($xpath->query('.//w:r', $paragraph) as $run) {
            if (!($run instanceof \DOMElement)) {
                continue;
            }

            $isYellow = $xpath->query('./w:rPr/w:highlight[@w:val="yellow"]', $run)->length > 0;
            if ($isYellow) {
                $hasYellow = true;
                continue;
            }

            foreach ($xpath->query('.//w:t', $run) as $t) {
                return false;
            }
        }

        return $hasYellow;
    }

    /**
     * Marca en el HTML interactivo los huecos del encabezado de apertura
     * (Trabajador, Cargo, CC, Fecha, Radicado) para rellenarlos después del cache.
     */
    private function prepareHeaderFieldsInHtml(DOMDocument $dom, DOMXPath $xpath): void
    {
        foreach ($xpath->query('//p|//td') as $p) {
            if (!($p instanceof \DOMElement)) {
                continue;
            }

            $plain = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $p->textContent)) ?? '');
            if ($plain === '' || !preg_match('/_{5,}|202X|XXX/u', $plain)) {
                continue;
            }

            $key = $this->headerKeyFromLabel($plain);
            if ($key === null) {
                continue;
            }

            if ($key === 'radicado') {
                $this->wrapRadicadoPlaceholder($dom, $p);
            } else {
                $this->wrapUnderscoreHeaderField($dom, $p, $key);
            }
        }
    }

    private function headerKeyFromLabel(string $plain): ?string
    {
        return match (true) {
            (bool) preg_match('/^Trabajador \(a\):/u', $plain) => 'nombre',
            (bool) preg_match('/^Cargo:/u', $plain) => 'cargo',
            (bool) preg_match('/^CC\.:/u', $plain) => 'cedula',
            (bool) preg_match('/^Fecha de expedici[oó]n:/u', $plain) => 'fecha',
            (bool) preg_match('/^Radicado:/u', $plain) => 'radicado',
            default => null,
        };
    }

    private function wrapUnderscoreHeaderField(DOMDocument $dom, \DOMElement $p, string $key): void
    {
        foreach ($this->textNodesOf($p) as $textNode) {
            if (!preg_match('/_{3,}/u', $textNode->nodeValue ?? '')) {
                continue;
            }
            $span = $dom->createElement('span');
            $span->setAttribute('class', 'doc-header-field');
            $span->setAttribute('data-header', $key);
            $span->setAttribute('data-blank', $textNode->nodeValue);
            $span->appendChild($dom->createTextNode($textNode->nodeValue));
            $textNode->parentNode?->replaceChild($span, $textNode);
        }
    }

    private function wrapRadicadoPlaceholder(DOMDocument $dom, \DOMElement $p): void
    {
        $nodes = $this->textNodesOf($p);
        $combined = '';
        foreach ($nodes as $node) {
            $combined .= $node->nodeValue;
        }
        if (!str_contains($combined, '202X-XXX')) {
            return;
        }

        $span = $dom->createElement('span');
        $span->setAttribute('class', 'doc-header-field');
        $span->setAttribute('data-header', 'radicado');
        $span->setAttribute('data-blank', '202X-XXX-01');
        $span->appendChild($dom->createTextNode('202X-XXX-01'));

        $inserted = false;
        foreach ($nodes as $node) {
            $val = $node->nodeValue ?? '';
            if (str_contains($val, '202X-XXX-01')) {
                $node->nodeValue = str_replace('202X-XXX-01', '', $val);
                $this->insertNodeAfter($span, $node);
                $inserted = true;
            } elseif (str_contains($val, '202X-XXX-')) {
                $node->nodeValue = str_replace('202X-XXX-', '', $val);
                $this->insertNodeAfter($span, $node);
                $inserted = true;
            } elseif ($inserted && trim($val) === '01') {
                $node->nodeValue = '';
            }
        }

        if (!$inserted) {
            $p->appendChild($span);
        }
    }

    private function insertNodeAfter(\DOMNode $newNode, \DOMNode $reference): void
    {
        $parent = $reference->parentNode;
        if (!$parent) {
            return;
        }
        if ($reference->nextSibling) {
            $parent->insertBefore($newNode, $reference->nextSibling);
        } else {
            $parent->appendChild($newNode);
        }
    }

    /**
     * @return array<int, \DOMText>
     */
    private function textNodesOf(\DOMNode $node): array
    {
        $nodes = [];
        if ($node instanceof \DOMText) {
            return [$node];
        }
        if (!$node->hasChildNodes()) {
            return [];
        }
        foreach (iterator_to_array($node->childNodes) as $child) {
            foreach ($this->textNodesOf($child) as $text) {
                $nodes[] = $text;
            }
        }
        return $nodes;
    }

    private function fillHeaderInHtml(string $html, array $header): string
    {
        foreach (['nombre', 'cargo', 'cedula', 'fecha', 'radicado'] as $key) {
            if (!array_key_exists($key, $header)) {
                continue;
            }
            $value = trim((string) $header[$key]);
            if ($value === '' && !in_array($key, ['fecha', 'radicado'], true)) {
                continue;
            }
            $safe = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $updated = preg_replace_callback(
                '/(<span\b[^>]*\bdata-header="' . preg_quote($key, '/') . '"[^>]*>)(.*?)(<\/span>)/s',
                static fn (array $m) => $m[1] . $safe . $m[3],
                $html,
                1
            );
            if (is_string($updated)) {
                $html = $updated;
            }
        }

        return $html;
    }

    /**
     * Rellena Trabajador / Cargo / CC / Fecha / Radicado en el XML de Word.
     * Se aplica después de los bloques amarillos para no alterar la firma del texto fijo.
     */
    private function fillHeaderInXml(string $xml, array $header): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        if (!$document->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return $xml;
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') as $paragraph) {
            $plain = '';
            foreach ($xpath->query('.//w:t', $paragraph) as $t) {
                $plain .= $t->textContent;
            }
            $plain = trim(preg_replace('/\s+/u', ' ', $plain) ?? '');
            if ($plain === '' || !preg_match('/_{5,}|202X|XXX/u', $plain)) {
                continue;
            }

            $key = $this->headerKeyFromLabel($plain);
            if ($key === null || !array_key_exists($key, $header)) {
                continue;
            }

            $value = trim((string) $header[$key]);
            if ($value === '' && !in_array($key, ['fecha', 'radicado'], true)) {
                continue;
            }

            if ($key === 'radicado') {
                $first = true;
                foreach ($xpath->query('.//w:t', $paragraph) as $t) {
                    $t->nodeValue = $first ? ('Radicado: ' . $value) : '';
                    $first = false;
                }
                continue;
            }

            foreach ($xpath->query('.//w:t', $paragraph) as $t) {
                if (!preg_match('/^_+$/u', trim((string) $t->textContent))) {
                    continue;
                }
                $t->nodeValue = $value;
                if (str_contains($value, ' ')) {
                    $t->setAttribute('xml:space', 'preserve');
                }
            }
        }

        return $document->saveXML() ?: $xml;
    }

    public function materializeDocx(ProcesoDisciplinario $proceso, string $template): string
    {
        return $this->buildDocx($proceso, $template, true);
    }

    /**
     * Calcula una firma Hash SHA-256 del texto no variable (no amarillo) del documento.
     */
    private function fixedTextSignature(DOMXPath $xpath): string
    {
        $parts = [];
        foreach ($xpath->query('//w:t') as $text) {
            $run       = $text->parentNode;
            $highlight = $xpath->query('./w:rPr/w:highlight[@w:val="yellow"]', $run);
            if ($highlight->length === 0) {
                $parts[] = $text->nodeValue;
            }
        }
        return hash('sha256', implode('|', $parts));
    }

    private function detectYellowBlocks(string $type): array
    {
        $filename = self::TEMPLATES[$type] ?? null;
        abort_unless($filename, 404, 'Tipo de documento no válido');

        $source = base_path('documentos/' . $filename);
        if (!is_file($source)) {
            $source = storage_path('app/plantillas/' . $filename);
        }
        abort_unless(is_file($source), 500, "No se encontró la plantilla oficial '{$filename}'.");

        $fileHash = md5_file($source);
        $cacheKey = "sipd_yellow_labels_{$type}_{$fileHash}";

        return Cache::rememberForever($cacheKey, function () use ($source) {
            $zip = new ZipArchive();
            abort_unless($zip->open($source) === true, 500, 'No se pudo abrir la plantilla oficial.');
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $document = new DOMDocument('1.0', 'UTF-8');
            $document->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $labels = [];
            foreach ($xpath->query('//w:p') as $paragraph) {
                $runs = $xpath->query('.//w:r[w:rPr/w:highlight[@w:val="yellow"]]', $paragraph);
                if ($runs->length === 0) {
                    continue;
                }
                $text = '';
                foreach ($runs as $run) {
                    foreach ($xpath->query('.//w:t', $run) as $node) {
                        $text .= $node->nodeValue;
                    }
                }
                $text = trim($text);
                $n = count($labels) + 1;
                $labels[] = $text !== '' ? $text : ('Campo editable ' . $n);
            }

            return $labels;
        });
    }

    /**
     * Convierte DOCX a PDF usando LibreOffice headless si está disponible.
     */
    private function convertDocxToPdf(string $docxPath): ?string
    {
        $libreOffice = $this->findLibreOffice();
        if ($libreOffice === null) {
            return null;
        }

        $tmpDir = storage_path('app');
        $cmd    = escapeshellarg($libreOffice)
                . ' --headless --convert-to pdf --outdir '
                . escapeshellarg($tmpDir) . ' '
                . escapeshellarg($docxPath)
                . (PHP_OS_FAMILY === 'Windows' ? ' 2>nul' : ' 2>/dev/null');

        @exec($cmd, $out, $code);

        $base     = pathinfo($docxPath, PATHINFO_FILENAME);
        $pdfGuess = $tmpDir . DIRECTORY_SEPARATOR . $base . '.pdf';

        return file_exists($pdfGuess) ? $pdfGuess : null;
    }

    private function findLibreOffice(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                'C:\\laragon\\bin\\libreoffice\\program\\soffice.exe',
            ];
            foreach ($candidates as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
            @exec('where soffice 2>nul', $o, $c);
            return ($c === 0 && !empty($o[0])) ? trim($o[0]) : null;
        }

        $unix = ['/usr/bin/soffice', '/usr/bin/libreoffice', '/usr/local/bin/soffice'];
        foreach ($unix as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        @exec('which soffice 2>/dev/null', $o, $c);
        return ($c === 0 && !empty($o[0])) ? trim($o[0]) : null;
    }
}
