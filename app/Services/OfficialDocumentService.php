<?php

namespace App\Services;

use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
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
        abort_unless(isset(self::BLOCKS[$type]), 404, 'Tipo de documento no válido');
        return self::BLOCKS[$type];
    }

    public function fieldSections(string $type): array
    {
        abort_unless(isset(self::SECTIONS[$type]), 404, 'Tipo de documento no válido');
        return self::SECTIONS[$type];
    }

    public function defaultTexts(string $type): array
    {
        return self::DEFAULT_TEXTS[$type] ?? [];
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

    public function getInteractiveDocumentHtml(string $tipo, array $currentValues = []): string
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
        $cacheKey = "sipd_interactive_doc_{$tipo}_{$fileHash}";

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

        return $html;
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
        
        $paragraphs = $xpath->query("//p");
        $yellowIndex = 0;
        $defaults = $this->defaultTexts($tipo);
        
        foreach ($paragraphs as $p) {
            // Sólo operar sobre DOMElement (no sobre DOMNameSpaceNode ni DOMNode base)
            if (!($p instanceof \DOMElement)) {
                continue;
            }

            // Lógica para detectar la línea de firma (muchos guiones bajos)
            if (preg_match('/^_{10,}$/', trim($p->textContent))) {
                $p->setAttribute('class', 'sig-zone');
                $p->setAttribute('style', 'text-align:center; min-height:60px; color:#94a3b8; font-size:12px; cursor:pointer;');
                // Limpiar el contenido original (los guiones) eliminando nodos hijos
                while ($p->firstChild) {
                    $p->removeChild($p->firstChild);
                }
                $txt = $dom->createElement('span', '(Firma Digital - Cargue su firma en el botón superior)');
                $p->appendChild($txt);
            }

            $yellowSpans = $xpath->query(".//span[contains(@style, 'background: yellow') or contains(@style, 'background-color: yellow') or contains(@style, 'background: #FFFF00')]", $p);
            if ($yellowSpans->length === 0) continue;
            
            $originalText = '';
            foreach ($yellowSpans as $span) {
                $originalText .= $span->textContent;
            }
            $originalText = trim($originalText);
            $placeholder = !empty($originalText) ? $originalText : ($defaults[$yellowIndex] ?? '');

            $firstSpan = $yellowSpans->item(0);
            
            $textarea = $dom->createElement('textarea');
            $textarea->setAttribute('name', "yellow_blocks_{$tipo}[{$yellowIndex}]");
            $textarea->setAttribute('class', "doc-interactive-field js-block-{$tipo}");
            $textarea->setAttribute('data-index', $yellowIndex);
            $textarea->setAttribute('placeholder', htmlspecialchars($placeholder));
            
            $rows = strlen($placeholder) > 80 ? '4' : '2';
            $textarea->setAttribute('rows', $rows);
            // Estilos para que parezca zona interactiva integrada al documento
            $textarea->setAttribute('style', 'width: 100%; font-family: inherit; font-size: inherit; padding: 6px; border: 1.5px dashed #fbbf24; border-radius: 4px; box-sizing: border-box; background: #fffbeb; transition: all 0.2s; outline: none; margin: 4px 0;');
            
            $firstSpan->parentNode->insertBefore($textarea, $firstSpan);
            
            foreach ($yellowSpans as $span) {
                $span->parentNode->removeChild($span);
            }
            $yellowIndex++;
        }

        return $dom->saveHTML();
    }

    // ─── Métodos Privados ──────────────────────────────────────────
    /**
     * Construye un DOCX temporal a partir de la plantilla original sin alterar la fuente oficial.
     */
    private function buildDocx(ProcesoDisciplinario $proceso, string $template, bool $strictValidation = true): string
    {
        $blocks      = $proceso->datos_oficiales['yellow_blocks'][$template] ?? [];
        $definitions = $this->blockDefinitions($template);

        if (!$strictValidation) {
            foreach ($definitions as $index => $label) {
                if (!isset($blocks[$index]) || trim((string) $blocks[$index]) === '') {
                    $blocks[$index] = '[ Pendiente: ' . $label . ' ]';
                }
            }
        } else {
            $this->validateBlocks($blocks, $definitions);
        }

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

        $zip->deleteName($entryName);
        $zip->addFromString($entryName, $updatedXml);
        $zip->close();

        return $output;
    }

    private function validateBlocks(array $blocks, array $definitions): void
    {
        foreach ($definitions as $index => $label) {
            if (!isset($blocks[$index]) || trim((string) $blocks[$index]) === '') {
                throw ValidationException::withMessages([
                    'yellow_blocks.' . $index => 'Completa el campo amarillo: ' . $label . '.',
                ]);
            }
        }
    }

    /**
     * Reemplaza los bloques resaltados en amarillo en el XML de Word.
     * Garantiza que el texto fijo no variable permanezca 100% inalterado.
     */
    private function replaceYellowBlocks(string $xml, array $blocks): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $before      = $this->fixedTextSignature($xpath);
        $paragraphs  = $xpath->query('//w:p');
        $yellowIndex = 0;

        foreach ($paragraphs as $paragraph) {
            $runs = $xpath->query('.//w:r[w:rPr/w:highlight[@w:val="yellow"]]', $paragraph);
            if ($runs->length === 0) {
                continue;
            }

            if (!array_key_exists($yellowIndex, $blocks)) {
                break;
            }

            $value     = (string) $blocks[$yellowIndex];
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

        if ($yellowIndex !== count($blocks)) {
            throw new \RuntimeException("La plantilla contiene {$yellowIndex} bloques amarillos, pero se esperaban " . count($blocks) . ".");
        }

        $after = $this->fixedTextSignature($xpath);
        if ($before !== $after) {
            throw new \RuntimeException('La generación alteraría texto fijo no variable de la plantilla oficial.');
        }

        return $document->saveXML();
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
