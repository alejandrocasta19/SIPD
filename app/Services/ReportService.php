<?php

namespace App\Services;

use App\Models\CasoAnexo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Language;

class ReportService
{
    public function statistics(Builder $query, ?string $from, ?string $to): array
    {
        $filtered = $this->applyDateRange($query, $from, $to);
        $abiertos = (clone $filtered)->whereNotIn('estado', ['Sancionado', 'Archivado']);
        $enProceso = (clone $abiertos)->conDocumentoDescargado()->count();
        $abiertosTotal = (clone $abiertos)->count();
        $sancionados = (clone $filtered)->where('estado', 'Sancionado')->count();
        $archivados = (clone $filtered)->where('estado', 'Archivado')->count();
        $pendientes = $abiertosTotal - $enProceso;
        $states = [
            'Pendiente' => $pendientes,
            'En proceso' => $enProceso,
            'Sancionado' => $sancionados,
            'Archivado' => $archivados,
        ];

        $periodSql = $filtered->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyRows = (clone $filtered)
            ->selectRaw("{$periodSql} AS period, estado, COUNT(*) AS total")
            ->groupBy(DB::raw($periodSql), 'estado')
            ->orderBy('period')
            ->get();

        $monthlyByPeriod = [];
        foreach ($monthlyRows as $row) {
            $monthlyByPeriod[$row->period][(string) ($row->estado ?: 'Sin estado')] = (int) $row->total;
        }

        [$periodStart, $periodEnd] = $this->periodBounds($filtered, $from, $to);
        $monthly = [];
        if ($periodStart && $periodEnd) {
            foreach (CarbonPeriod::create($periodStart, '1 month', $periodEnd) as $month) {
                $period = $month->format('Y-m');
                $stateTotals = $monthlyByPeriod[$period] ?? [];
                $monthly[] = [
                    'period' => $period,
                    'states' => $stateTotals,
                    'total' => array_sum($stateTotals),
                ];
            }
        }

        $dailySql = $filtered->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d')";
        $dailyRows = (clone $filtered)
            ->selectRaw("{$dailySql} AS period, COUNT(*) AS total")
            ->groupBy(DB::raw($dailySql))
            ->orderBy('period')
            ->get();
        $dailyByPeriod = [];
        foreach ($dailyRows as $row) {
            if ($row->period) {
                $dailyByPeriod[$row->period] = (int) $row->total;
            }
        }

        $dailyBounds = (clone $filtered)
            ->selectRaw('MIN(created_at) AS min_date, MAX(created_at) AS max_date')
            ->first();
        $dailyStart = $from
            ? Carbon::parse($from)->startOfDay()
            : ($dailyBounds && $dailyBounds->min_date ? Carbon::parse($dailyBounds->min_date)->startOfDay() : null);
        $dailyEnd = $to
            ? Carbon::parse($to)->startOfDay()
            : ($dailyBounds && $dailyBounds->max_date ? Carbon::parse($dailyBounds->max_date)->startOfDay() : null);
        $daily = [];
        if ($dailyStart && $dailyEnd && $dailyStart->lte($dailyEnd)) {
            foreach (CarbonPeriod::create($dailyStart, '1 day', $dailyEnd) as $day) {
                $period = $day->format('Y-m-d');
                $daily[] = [
                    'period' => $period,
                    'total' => $dailyByPeriod[$period] ?? 0,
                ];
            }
        }

        $weekSql = $filtered->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%W', created_at)"
            : "DATE_FORMAT(created_at, '%x-W%v')"; // ISO Year-Week in MySQL

        $weeklyRows = (clone $filtered)
            ->selectRaw("{$weekSql} AS period, COUNT(*) AS total")
            ->groupBy(DB::raw($weekSql))
            ->orderBy('period')
            ->get();

        $weekly = [];
        // To build the exact weeks within range, we'll just return the raw grouped ones to avoid heavy CarbonPeriod week logic
        foreach ($weeklyRows as $row) {
            if ($row->period) {
                $weekly[] = [
                    'period' => $row->period,
                    'total' => (int) $row->total,
                ];
            }
        }

        $faults = $this->groupedCounts(
            $filtered,
            "COALESCE(NULLIF(tipo_falta, ''), 'Sin tipo de falta')",
            'tipo_falta'
        );
        $modalidadCargoExpr = $filtered->getConnection()->getDriverName() === 'sqlite'
            ? "CASE WHEN NULLIF(modalidad, '') IS NOT NULL AND NULLIF(cargo, '') IS NOT NULL THEN modalidad || ' - ' || cargo ELSE COALESCE(NULLIF(modalidad, ''), NULLIF(cargo, ''), 'Sin modalidad / cargo') END"
            : "COALESCE(NULLIF(CONCAT_WS(' - ', NULLIF(modalidad, ''), NULLIF(cargo, '')), ''), 'Sin modalidad / cargo')";

        $total = array_sum($states);
        $pendientes = (int) ($states['Pendiente'] ?? 0);
        $enProceso = (int) ($states['En Proceso'] ?? 0);
        $sancionados = (int) ($states['Sancionado'] ?? 0);
        $archivados = (int) ($states['Archivado'] ?? 0);

        $cerrados = (clone $filtered)
            ->whereIn('estado', ['Sancionado', 'Archivado'])
            ->get(['created_at', 'updated_at']);
        $dias = $cerrados->map(function ($caso) {
            if (!$caso->created_at || !$caso->updated_at) {
                return null;
            }
            return $caso->created_at->diffInDays($caso->updated_at);
        })->filter(fn ($n) => $n !== null);

        $sinDescargos = (clone $filtered)
            ->where(function ($q) {
                $q->whereNull('descargos')->orWhere('descargos', '');
            })
            ->count();

        return [
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'states' => $states,
            'daily' => $daily,
            'monthly' => $monthly,
            'weekly' => $weekly,
            'pending_faults' => $faults,
            'by_modalidad' => $this->groupedCounts(
                $filtered,
                $modalidadCargoExpr,
                'modalidad_cargo'
            ),
            'by_ruta' => $this->groupedCounts(
                $filtered,
                "COALESCE(NULLIF(ruta, ''), 'Sin ruta')",
                'ruta'
            ),
            'reincidencias' => $this->reincidenciaStats($filtered),
            'top_reincidentes' => $this->topReincidentes($filtered),
            'anexos' => $this->anexoStats($filtered),
            'descargos' => [
                'sin' => $sinDescargos,
                'con' => max(0, $total - $sinDescargos),
            ],
            'kpis' => [
                'pendientes' => $pendientes,
                'en_proceso' => $enProceso,
                'sancionados' => $sancionados,
                'archivados' => $archivados,
                'finalizados' => $sancionados + $archivados,
                'sin_responsable' => (clone $filtered)->whereNull('user_id')->count(),
                'prom_dias' => $dias->count() ? (int) round($dias->avg()) : null,
            ],
            'meta' => $this->reportMeta(
                'Informe de gestión disciplinaria',
                'Procesos disciplinarios internos de Cootranshuila',
                $from,
                $to
            ),
        ];
    }

    public function reportMeta(string $titulo, ?string $subtitulo = null, ?string $from = null, ?string $to = null): array
    {
        $user = auth()->user();

        return [
            'empresa' => 'Cooperativa de Transportadores del Huila — Cootranshuila',
            'sistema' => 'SIPD — Sistema Interno de Procesos Disciplinarios',
            'titulo' => $titulo,
            'subtitulo' => $subtitulo ?: 'Uso interno · Coordinación de RH',
            'periodo' => ($from ?: 'Inicio') . ' a ' . ($to ?: 'Actualidad'),
            'generado_el' => now()->format('d/m/Y H:i'),
            'generado_por' => $user->name ?? 'Sistema SIPD',
            'equipo' => $user ? $user->etiquetaEquipo() : null,
            'filtro' => request('q'),
            'uso' => 'Documento de uso interno de Coordinación de RH. No divulgar fuera de Cootranshuila.',
        ];
    }

    private function groupedCounts(Builder $filtered, string $expr, string $alias): array
    {
        return (clone $filtered)
            ->selectRaw("{$expr} AS {$alias}, COUNT(*) AS total")
            ->groupBy(DB::raw($expr))
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($alias) {
                return ['label' => (string) $row->{$alias}, 'total' => (int) $row->total];
            })
            ->values()
            ->all();
    }

    private function reincidenciaStats(Builder $filtered): array
    {
        $porCedula = (clone $filtered)
            ->whereNotNull('cedula')
            ->where('cedula', '!=', '')
            ->selectRaw('cedula, COUNT(*) AS total')
            ->groupBy('cedula')
            ->pluck('total');

        return [
            'primera_vez' => (int) $porCedula->filter(fn ($n) => (int) $n === 1)->count(),
            'reincidentes' => (int) $porCedula->filter(fn ($n) => (int) $n > 1)->count(),
        ];
    }

    private function topReincidentes(Builder $filtered): array
    {
        return (clone $filtered)
            ->whereNotNull('cedula')
            ->where('cedula', '!=', '')
            ->selectRaw('cedula, MAX(nombre) AS nombre, COUNT(*) AS total')
            ->groupBy('cedula')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'cedula' => (string) $row->cedula,
                    'nombre' => (string) $row->nombre,
                    'total' => (int) $row->total,
                ];
            })
            ->all();
    }

    private function anexoStats(Builder $filtered): array
    {
        $base = CasoAnexo::query()->whereIn(
            'caso_id',
            (clone $filtered)->select('disciplinario.id')
        );

        $porTipo = (clone $base)
            ->select('tipo', DB::raw('COUNT(*) AS total'))
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $porEstado = (clone $base)
            ->select('estado', DB::raw('COUNT(*) AS total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $archivoPrevio = (int) $porTipo
            ->reject(fn ($total, $tipo) => in_array($tipo, ['terminacion', CasoAnexo::TIPO_FIRMA_GERENTE], true))
            ->sum();
        $pendiente = (int) ($porEstado[CasoAnexo::ESTADO_PENDIENTE_FIRMA] ?? 0);
        $firmado = (clone $base)->where(function ($sub) {
            $sub->where('estado', CasoAnexo::ESTADO_FIRMADO)
                ->orWhereIn('tipo', ['terminacion', CasoAnexo::TIPO_FIRMA_GERENTE]);
        })->count();

        return [
            'total' => (clone $base)->count(),
            'archivo_previo' => $archivoPrevio,
            'pendiente_firma' => $pendiente,
            'firmado' => $firmado,
        ];
    }

    public function applyDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return $query;
    }

    public function globalPdf(array $data)
    {
        $assets = $this->createChartAssets($data);
        try {
            $data['charts'] = array_map(function (array $asset) {
                return [
                    'title' => $asset['title'],
                    'src' => 'data:image/png;base64,' . base64_encode($asset['contents']),
                ];
            }, $assets);

            return Pdf::loadView('reports.global-pdf', ['data' => $data])
                ->setPaper('a4')
                ->download('informe-disciplinario-cootranshuila.pdf');
        } finally {
            $this->deleteChartAssets($assets);
        }
    }

    public function globalWord(array $data)
    {
        $assets = $this->createChartAssets($data);
        try {
            $layout = $this->globalLayout($data);
            $layout['chart_assets'] = $assets;

            return $this->downloadWordDocument(
                $this->buildWordFromLayout($layout, false),
                'informe-disciplinario-cootranshuila.docx'
            );
        } finally {
            $this->deleteChartAssets($assets);
        }
    }

    public function globalExcel(array $data)
    {
        $assets = $this->createChartAssets($data);
        try {
            $layout = $this->globalLayout($data);
            $layout['chart_assets'] = $assets;

            return $this->downloadSpreadsheet(
                $this->buildExcelFromLayout($layout, false),
                'informe-disciplinario-cootranshuila.xlsx'
            );
        } finally {
            $this->deleteChartAssets($assets);
        }
    }

    public function casesPdf($cases)
    {
        return Pdf::loadView('reports.cases-pdf', [
            'cases' => $cases,
            'meta' => $this->reportMeta(
                'Relación de expedientes seleccionados',
                'Listado operativo del régimen disciplinario interno'
            ),
        ])
            ->setPaper('a4', 'landscape')
            ->download('expedientes-disciplinarios-cootranshuila.pdf');
    }

    public function casesWord($cases)
    {
        return $this->downloadWordDocument(
            $this->buildWordFromLayout($this->casesLayout($cases), true),
            'expedientes-disciplinarios-cootranshuila.docx'
        );
    }

    public function casesExcel($cases)
    {
        return $this->downloadSpreadsheet(
            $this->buildExcelFromLayout($this->casesLayout($cases), true),
            'expedientes-disciplinarios-cootranshuila.xlsx'
        );
    }

    private function createChartAssets(array $data): array
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('La extensión GD de PHP es necesaria para incluir los gráficos en los informes.');
        }

        $specs = $this->reportChartSpecs($data);
        $assets = [];
        try {
            foreach ($specs as $spec) {
                $contents = $this->renderChartPng($spec);
                $temporaryPath = tempnam(storage_path('app'), 'sipd-chart-');
                if ($temporaryPath === false) {
                    throw new \RuntimeException('No se pudo preparar una gráfica para el informe.');
                }
                $path = $temporaryPath . '.png';
                if (!rename($temporaryPath, $path)) {
                    if (is_file($temporaryPath)) {
                        unlink($temporaryPath);
                    }
                    throw new \RuntimeException('No se pudo preparar una gráfica para el informe.');
                }
                if (file_put_contents($path, $contents) === false) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                    throw new \RuntimeException('No se pudo preparar una gráfica para el informe.');
                }
                $assets[] = [
                    'title' => $spec['title'],
                    'path' => $path,
                    'contents' => $contents,
                ];
            }
        } catch (\Throwable $exception) {
            $this->deleteChartAssets($assets);
            throw $exception;
        }

        return $assets;
    }

    private function deleteChartAssets(array $assets): void
    {
        foreach ($assets as $asset) {
            if (!empty($asset['path']) && is_file($asset['path'])) {
                unlink($asset['path']);
            }
        }
    }

    private function reportChartSpecs(array $data): array
    {
        $daily = $data['daily'] ?? [];
        $monthly = $data['monthly'] ?? [];
        $weekly = $data['weekly'] ?? [];
        $states = $data['states'] ?? [];
        $dailyLabels = [];
        foreach ($daily as $day) {
            $dailyLabels[] = !empty($day['period'])
                ? Carbon::createFromFormat('Y-m-d', $day['period'])->locale('es')->isoFormat('DD MMM')
                : '';
        }
        $monthlyLabels = [];
        foreach ($monthly as $month) {
            $monthlyLabels[] = !empty($month['period'])
                ? Carbon::createFromFormat('Y-m', $month['period'])->locale('es')->isoFormat('MMM YY')
                : '';
        }
        $weeklyLabels = [];
        foreach ($weekly as $week) {
            $period = (string) ($week['period'] ?? '');
            if (preg_match('/^(\d{4})-W?(\d{1,2})$/', $period, $matches)) {
                $weeklyLabels[] = 'Sem ' . (int) $matches[2] . ' - ' . $matches[1];
            } else {
                $weeklyLabels[] = $period;
            }
        }

        return [
            [
                'title' => 'Casos diarios',
                'kind' => 'line',
                'labels' => $dailyLabels,
                'values' => array_map(function ($day) {
                    return (int) ($day['total'] ?? 0);
                }, $daily),
                'color' => [8, 145, 178],
            ],
            [
                'title' => 'Casos semanales',
                'kind' => 'line',
                'labels' => $weeklyLabels,
                'values' => array_map(function ($week) {
                    return (int) ($week['total'] ?? 0);
                }, $weekly),
                'color' => [37, 99, 235],
            ],
            [
                'title' => 'Casos por mes',
                'kind' => 'line',
                'labels' => $monthlyLabels,
                'values' => array_map(function ($month) {
                    return (int) ($month['total'] ?? array_sum($month['states'] ?? []));
                }, $monthly),
                'color' => [22, 163, 74],
            ],
            [
                'title' => 'Estado global',
                'kind' => 'doughnut',
                'labels' => array_keys($states),
                'values' => array_map('intval', array_values($states)),
                'color' => [245, 158, 11],
                'colors' => [[245, 158, 11], [37, 99, 235], [220, 38, 38], [100, 116, 139]],
            ],
            [
                'title' => 'Modalidad y cargo',
                'kind' => 'bar',
                'labels' => array_column(array_slice($data['by_modalidad'] ?? [], 0, 8), 'label'),
                'values' => array_map('intval', array_column(array_slice($data['by_modalidad'] ?? [], 0, 8), 'total')),
                'color' => [37, 99, 235],
            ],
            [
                'title' => 'Tipos de falta',
                'kind' => 'bar',
                'labels' => array_column(array_slice($data['pending_faults'] ?? [], 0, 8), 'label'),
                'values' => array_map('intval', array_column(array_slice($data['pending_faults'] ?? [], 0, 8), 'total')),
                'color' => [245, 158, 11],
            ],
        ];
    }

    private function renderChartPng(array $spec): string
    {
        $width = 960;
        $height = 480;
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            throw new \RuntimeException('No se pudo crear una imagen para las gráficas del informe.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 15, 23, 42);
        $muted = imagecolorallocate($image, 100, 116, 139);
        $grid = imagecolorallocate($image, 226, 232, 240);
        $color = imagecolorallocate($image, $spec['color'][0], $spec['color'][1], $spec['color'][2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        if ($spec['kind'] === 'line') {
            $this->drawLineChart($image, $spec, $ink, $muted, $grid, $color);
        } elseif ($spec['kind'] === 'doughnut') {
            $this->drawDoughnutChart($image, $spec, $ink, $muted);
        } else {
            $this->drawBarChart($image, $spec, $ink, $muted, $grid, $color);
        }

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);
        if (!is_string($contents) || $contents === '') {
            throw new \RuntimeException('No se pudo codificar una gráfica del informe.');
        }

        return $contents;
    }

    private function drawLineChart($image, array $spec, int $ink, int $muted, int $grid, int $color): void
    {
        $left = 86;
        $top = 90;
        $plotWidth = 820;
        $plotHeight = 300;
        $values = $spec['values'];
        $max = max(1, $values ? max($values) : 0);
        $pointCenter = imagecolorallocate($image, 255, 255, 255);
        $steps = 4;
        for ($step = 0; $step <= $steps; $step++) {
            $y = $top + (int) round($plotHeight * $step / $steps);
            imageline($image, $left, $y, $left + $plotWidth, $y, $grid);
            $value = (int) round($max * ($steps - $step) / $steps);
            imagestring($image, 2, 42, $y - 7, (string) $value, $muted);
        }

        if (!$values) {
            imagestring($image, 4, 370, 235, 'Sin datos para el periodo', $muted);
            return;
        }

        $points = [];
        $count = count($values);
        foreach ($values as $index => $value) {
            $x = $count === 1 ? $left + (int) ($plotWidth / 2) : $left + (int) round($plotWidth * $index / ($count - 1));
            $y = $top + $plotHeight - (int) round($plotHeight * max(0, (int) $value) / $max);
            $points[] = [$x, $y];
        }
        for ($index = 1; $index < count($points); $index++) {
            imagesetthickness($image, 4);
            imageline($image, $points[$index - 1][0], $points[$index - 1][1], $points[$index][0], $points[$index][1], $color);
            imagesetthickness($image, 1);
        }
        foreach ($points as $index => $point) {
            imagefilledellipse($image, $point[0], $point[1], 12, 12, $color);
            imagefilledellipse($image, $point[0], $point[1], 5, 5, $pointCenter);
            $labelEvery = max(1, (int) ceil($count / 10));
            if ($index % $labelEvery === 0 || $index === $count - 1) {
                $label = $this->chartText((string) ($spec['labels'][$index] ?? ''));
                imagestring($image, 2, max($left, min($point[0] - 24, $left + $plotWidth - 54)), $top + $plotHeight + 14, substr($label, 0, 16), $muted);
            }
        }
    }

    private function drawBarChart($image, array $spec, int $ink, int $muted, int $grid, int $color): void
    {
        $labels = $spec['labels'];
        $values = $spec['values'];
        if (!$values) {
            imagestring($image, 4, 370, 235, 'Sin datos para el periodo', $muted);
            return;
        }
        $left = 360;
        $top = 86;
        $plotWidth = 520;
        $max = max(1, max($values));
        $rowHeight = min(42, (int) floor(350 / count($values)));
        $barHeight = max(12, min(24, $rowHeight - 8));
        foreach ($values as $index => $value) {
            $y = $top + $index * $rowHeight;
            $label = $this->chartText((string) ($labels[$index] ?? ''));
            imagestring($image, 3, 36, $y + 2, substr($label, 0, 44), $ink);
            imageline($image, $left, $y + $rowHeight - 4, $left + $plotWidth, $y + $rowHeight - 4, $grid);
            $barWidth = (int) round($plotWidth * max(0, (int) $value) / $max);
            if ($barWidth > 0) {
                imagefilledrectangle($image, $left, $y + 5, $left + $barWidth, $y + 5 + $barHeight, $color);
            }
            imagestring($image, 3, min($left + $plotWidth + 8, $left + $barWidth + 10), $y + 9, (string) $value, $ink);
        }
    }

    private function drawDoughnutChart($image, array $spec, int $ink, int $muted): void
    {
        $labels = $spec['labels'];
        $values = $spec['values'];
        $total = array_sum($values);
        if ($total <= 0) {
            imagestring($image, 4, 370, 235, 'Sin casos registrados', $muted);
            return;
        }

        $colors = $spec['colors'] ?? [];
        $start = 270;
        $centerX = 330;
        $centerY = 255;
        $diameter = 310;
        foreach ($values as $index => $value) {
            if ($value <= 0) {
                continue;
            }
            $angle = (int) round(360 * $value / $total);
            $rgb = $colors[$index] ?? [100, 116, 139];
            $slice = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledarc($image, $centerX, $centerY, $diameter, $diameter, $start, $start + $angle, $slice, IMG_ARC_PIE);
            $start += $angle;
        }
        imagefilledellipse($image, $centerX, $centerY, 172, 172, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, $centerX - 30, $centerY - 10, (string) $total, $ink);
        imagestring($image, 2, $centerX - 25, $centerY + 12, 'CASOS', $muted);

        $y = 145;
        foreach ($values as $index => $value) {
            if ($value <= 0) {
                continue;
            }
            $rgb = $colors[$index] ?? [100, 116, 139];
            $slice = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledrectangle($image, 600, $y, 618, $y + 17, $slice);
            $label = $this->chartText((string) ($labels[$index] ?? ''));
            $percent = number_format($value * 100 / $total, 1, ',', '.');
            imagestring($image, 3, 630, $y + 2, substr($label, 0, 24) . '  ' . $value . ' (' . $percent . '%)', $ink);
            $y += 42;
        }
    }

    private function chartText(string $text): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        return $converted === false ? $text : $converted;
    }

    private function globalLayout(array $data): array
    {
        $meta = $data['meta'] ?? $this->reportMeta('Informe de gestión disciplinaria');
        $total = (int) ($data['total'] ?? 0);
        $kpis = $data['kpis'] ?? [];
        $anexos = $data['anexos'] ?? [];
        $reinc = $data['reincidencias'] ?? [];
        $pct = fn ($n) => $this->pct($n, $total);

        $stateRows = [];
        foreach ($data['states'] ?? [] as $state => $count) {
            $stateRows[] = [$state, $count, $pct($count)];
        }
        $monthRows = [];
        foreach ($data['monthly'] ?? [] as $month) {
            $st = $month['states'] ?? [];
            $monthRows[] = [
                $month['period'],
                $month['total'] ?? array_sum($st),
                $st['Pendiente'] ?? 0,
                $st['En Proceso'] ?? 0,
                $st['Sancionado'] ?? 0,
                $st['Archivado'] ?? 0,
            ];
        }
        $weekRows = [];
        foreach ($data['weekly'] ?? [] as $w) {
            $parts = explode('-W', (string)$w['period']);
            $weekTitle = count($parts) === 2 ? ('Sem ' . $parts[1] . ' (' . $parts[0] . ')') : $w['period'];
            $weekRows[] = [$weekTitle, $w['total']];
        }
        $cargoRows = [];
        foreach (array_values($data['by_modalidad'] ?? []) as $i => $row) {
            $cargoRows[] = [$i + 1, $row['label'], $row['total'], $pct($row['total'])];
        }
        $faltaRows = [];
        foreach (array_values($data['pending_faults'] ?? []) as $i => $row) {
            $faltaRows[] = [$i + 1, $row['label'], $row['total'], $pct($row['total'])];
        }
        $rutaRows = [];
        foreach ($data['by_ruta'] ?? [] as $row) {
            $rutaRows[] = [$row['label'], $row['total'], $pct($row['total'])];
        }
        $reincTop = [];
        foreach (array_values($data['top_reincidentes'] ?? []) as $i => $row) {
            $reincTop[] = [$i + 1, $row['nombre'], $row['cedula'], $row['total']];
        }

        $lead = 'Este informe consolida el comportamiento de los procesos disciplinarios de Cootranshuila en el período '
            . ($meta['periodo'] ?? (($data['from'] ?: 'Inicio') . ' a ' . ($data['to'] ?: 'Actualidad')))
            . '. Incluye estados del expediente, faltas más frecuentes, cargos involucrados, rutas, reincidencias y el avance documental de anexos.';
        if (!empty($meta['filtro'])) {
            $lead .= ' Filtro aplicado: ' . $meta['filtro'] . '.';
        }

        $blocks = [
            [
                'title' => '1. Distribución por estado',
                'note' => 'Estado actual de cada expediente en el período.',
                'headers' => ['Estado', 'Cantidad', 'Participación'],
                'rows' => $stateRows,
                'empty' => 'Sin procesos en el período.',
            ],
            [
                'title' => '2. Anexos del expediente',
                'note' => 'Escaneos firmados por gerencia y documentos de un caso anterior.',
                'headers' => ['Concepto', 'Cantidad'],
                'rows' => [
                    ['Total de anexos', $anexos['total'] ?? 0],
                    ['Archivo previo', $anexos['archivo_previo'] ?? 0],
                    ['Firmados', $anexos['firmado'] ?? 0],
                ],
            ],
            [
                'title' => '3. Volumen mensual',
                'note' => 'Casos registrados en cada mes.',
                'headers' => ['Mes', 'Casos'],
                'rows' => $monthRows,
                'empty' => 'Sin movimiento mensual.',
            ],
            [
                'title' => '4. Volumen semanal',
                'note' => 'Tendencia de casos por semana.',
                'headers' => ['Semana', 'Casos'],
                'rows' => $weekRows,
                'empty' => 'Sin datos semanales.',
            ],
            [
                'title' => '4. Modalidad y cargo',
                'note' => 'Modalidad del proceso y cargo del trabajador.',
                'headers' => ['#', 'Modalidad', 'Casos', 'Participación'],
                'rows' => $cargoRows,
                'empty' => 'Sin modalidades registradas.',
            ],
            [
                'title' => '5. Faltas más frecuentes',
                'note' => 'Tipos de falta registrados en el período.',
                'headers' => ['#', 'Tipo de falta', 'Casos', 'Participación'],
                'rows' => $faltaRows,
                'empty' => 'Sin tipos de falta registrados.',
            ],
            [
                'title' => '6. Rutas',
                'headers' => ['Ruta', 'Casos', 'Participación'],
                'rows' => $rutaRows,
                'empty' => 'Sin rutas registradas.',
            ],
            [
                'title' => '7. Descargos y seguimiento',
                'headers' => ['Concepto', 'Cantidad'],
                'rows' => [
                    ['Con descargos registrados', $data['descargos']['con'] ?? 0],
                    ['Sin descargos', $data['descargos']['sin'] ?? 0],
                    ['Expedientes sin responsable de RH', $kpis['sin_responsable'] ?? 0],
                    ['Duración promedio de casos cerrados', isset($kpis['prom_dias']) && $kpis['prom_dias'] !== null ? $kpis['prom_dias'] . ' días' : '—'],
                ],
            ],
            [
                'title' => '8. Reincidencias',
                'note' => 'Trabajadores identificados por cédula. Reincidente: más de un proceso en el período.',
                'headers' => ['Concepto', 'Cantidad'],
                'rows' => [
                    ['Primera vez', $reinc['primera_vez'] ?? 0],
                    ['Reincidentes', $reinc['reincidentes'] ?? 0],
                ],
            ],
        ];
        if ($reincTop) {
            $blocks[] = [
                'title' => '',
                'headers' => ['#', 'Trabajador', 'Cédula', 'Procesos'],
                'rows' => $reincTop,
            ];
        }

        return [
            'meta' => $meta,
            'titulo' => $meta['titulo'] ?? 'Informe de gestión disciplinaria',
            'lead' => $lead,
            'kpis' => [
                ['Total de casos', $total],
                ['Pendientes', $kpis['pendientes'] ?? 0],
                ['En proceso', $kpis['en_proceso'] ?? 0],
                ['Finalizados', $kpis['finalizados'] ?? 0],
            ],
            'blocks' => $blocks,
            'footer' => ($meta['uso'] ?? '') . ' Generado por ' . ($meta['generado_por'] ?? 'SIPD')
                . (!empty($meta['equipo']) ? ' · ' . $meta['equipo'] : '') . '.',
        ];
    }

    private function casesLayout($cases): array
    {
        $meta = $this->reportMeta(
            'Relación de expedientes seleccionados',
            'Listado operativo del régimen disciplinario interno'
        );
        $rows = [];
        $notas = [];
        foreach ($cases as $case) {
            $rows[] = [
                $case->codigoProceso(),
                $case->numeroRadicado(),
                $case->nombre,
                $case->cedula ?: 'Pendiente',
                \App\Support\Modalidades::textoPlaca($case->modalidad, $case->placa),
                $case->ruta ?: '—',
                \App\Support\Modalidades::etiquetaCaso($case->modalidad, $case->cargo),
                $case->tipo_falta ?: 'No especificada',
                $case->estadoVisible(),
                $case->fecha_falta ? $case->fecha_falta->format('d/m/Y') : '—',
                $case->created_at ? $case->created_at->format('d/m/Y') : '—',
                optional($case->user)->name ?: 'Sin asignar',
                (int) ($case->anexos_count ?? 0),
            ];
            if (filled($case->decision_final) || filled($case->observacion) || filled($case->descargos)) {
                $notas[] = [
                    $case->codigoProceso(),
                    $case->descargos ? Str::limit(strip_tags((string) $case->descargos), 180) : '—',
                    $case->decision_final ?: ($case->observacion ? Str::limit((string) $case->observacion, 180) : '—'),
                ];
            }
        }

        $blocks = [[
            'title' => '',
            'headers' => [
                'Proceso', 'Radicado', 'Trabajador', 'Cédula', 'Placa/Ruta', 'Ruta',
                'Cargo', 'Tipo de falta', 'Estado', 'Fecha falta', 'Fecha registro', 'Abogado', 'Anexos',
            ],
            'rows' => $rows,
            'empty' => 'Sin expedientes en la selección.',
        ]];
        if ($notas) {
            $blocks[] = [
                'title' => 'Notas y observaciones',
                'headers' => ['Proceso', 'Descargos', 'Decisión / Observación'],
                'rows' => $notas,
            ];
        }

        return [
            'meta' => $meta,
            'titulo' => $meta['titulo'],
            'lead' => 'Listado operativo de ' . $cases->count() . ' expediente' . ($cases->count() === 1 ? '' : 's')
                . ' del régimen disciplinario interno de Cootranshuila. Los datos corresponden a la selección hecha en SIPD al momento de generar este documento.',
            'kpis' => [],
            'blocks' => $blocks,
            'footer' => $meta['uso'] ?? '',
        ];
    }

    private function buildWordFromLayout(array $layout, bool $landscape): PhpWord
    {
        $meta = $layout['meta'] ?? [];
        $word = new PhpWord();
        $word->getSettings()->setThemeFontLang(new Language(Language::ES_ES));
        $section = $word->addSection(array_merge([
            'marginTop' => 700,
            'marginBottom' => 700,
            'marginLeft' => 800,
            'marginRight' => 800,
        ], $landscape ? ['orientation' => 'landscape'] : []));
        $section->addHeader()->addText('COOTRANSHUILA  ·  SIPD  ·  Uso interno', ['bold' => true, 'size' => 9, 'color' => '006837']);
        $section->addFooter()->addPreserveText(
            ($meta['uso'] ?? 'Uso interno') . '  ·  Página {PAGE} de {NUMPAGES}',
            ['size' => 8, 'color' => '64748B']
        );

        $mast = $section->addTable(['borderSize' => 0, 'cellMargin' => 40, 'width' => 5000, 'unit' => 'pct']);
        $mast->addRow();
        $logoCell = $mast->addCell(1200);
        $logo = public_path('images/logo-cootranshuila.png');
        if (is_file($logo)) {
            $logoCell->addImage($logo, ['height' => 40]);
        }
        $copy = $mast->addCell(2600);
        $copy->addText('Cooperativa de Transportadores del Huila', ['size' => 8, 'color' => '64748B']);
        $copy->addText('COOTRANSHUILA', ['bold' => true, 'size' => 16, 'color' => '006837']);
        $copy->addText($meta['sistema'] ?? 'SIPD', ['size' => 9, 'color' => '14532D']);
        $side = $mast->addCell(1200);
        $side->addText('Uso interno', ['bold' => true, 'size' => 8, 'color' => '475569'], ['alignment' => Jc::RIGHT]);
        $side->addText($meta['generado_el'] ?? '', ['size' => 8, 'color' => '475569'], ['alignment' => Jc::RIGHT]);
        $side->addText($meta['generado_por'] ?? '', ['size' => 8, 'color' => '475569'], ['alignment' => Jc::RIGHT]);

        $band = $section->addTable(['width' => 5000, 'unit' => 'pct']);
        $band->addRow();
        $band->addCell(5000, ['bgColor' => '006837'])->addText($layout['titulo'] ?? '', ['bold' => true, 'size' => 13, 'color' => 'FFFFFF']);
        $section->addTextBreak(1);
        $section->addText($layout['lead'] ?? '', ['size' => 10, 'color' => '475569']);
        $section->addTextBreak(1);

        if (!empty($layout['kpis'])) {
            $kpiTable = $section->addTable([
                'borderSize' => 4,
                'borderColor' => 'D1D5DB',
                'cellMargin' => 80,
                'width' => 5000,
                'unit' => 'pct',
            ]);
            $kpiTable->addRow();
            foreach ($layout['kpis'] as $kpi) {
                $cell = $kpiTable->addCell(1250, ['bgColor' => 'F8FAFC', 'valign' => 'center']);
                $cell->addText(mb_strtoupper((string) $kpi[0]), ['size' => 7, 'color' => '64748B', 'bold' => true], ['alignment' => Jc::CENTER]);
                $cell->addText((string) $kpi[1], ['bold' => true, 'size' => 16, 'color' => '006837'], ['alignment' => Jc::CENTER]);
            }
            $section->addTextBreak(1);
        }

        if (!empty($layout['chart_assets'])) {
            $section->addText('Visualizaciones del período', ['bold' => true, 'size' => 12, 'color' => '006837']);
            foreach (array_chunk($layout['chart_assets'], 2) as $chartRow) {
                $chartTable = $section->addTable([
                    'borderSize' => 0,
                    'cellMargin' => 60,
                    'width' => 5000,
                    'unit' => 'pct',
                ]);
                $chartTable->addRow();
                foreach ($chartRow as $asset) {
                    $cell = $chartTable->addCell(2500, ['valign' => 'center']);
                    $cell->addText($asset['title'], ['bold' => true, 'size' => 8, 'color' => '14532D'], ['alignment' => Jc::CENTER]);
                    $cell->addImage($asset['path'], [
                        'width' => 250,
                        'height' => 125,
                        'alignment' => Jc::CENTER,
                    ]);
                }
                if (count($chartRow) === 1) {
                    $chartTable->addCell(2500);
                }
            }
            $section->addTextBreak(1);
        }

        foreach ($layout['blocks'] as $block) {
            if (!empty($block['title'])) {
                $section->addText($block['title'], ['bold' => true, 'size' => 11, 'color' => '006837']);
            }
            if (!empty($block['note'])) {
                $section->addText($block['note'], ['size' => 8, 'italic' => true, 'color' => '64748B']);
            }
            $rows = $block['rows'] ?? [];
            if ($rows === [] && !empty($block['empty'])) {
                $rows = [[$block['empty']]];
                while (count($rows[0]) < count($block['headers'])) {
                    $rows[0][] = '';
                }
            }
            $this->addWordTable($section, '', $block['headers'], $rows);
        }

        if (!empty($layout['footer'])) {
            $section->addText($layout['footer'], ['size' => 8, 'color' => '64748B']);
        }

        return $word;
    }

    private function buildExcelFromLayout(array $layout, bool $landscape): Spreadsheet
    {
        $meta = $layout['meta'] ?? [];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Informe');
        $setup = $sheet->getPageSetup();
        $setup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setOrientation($landscape ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
        $setup->setFitToPage(true);
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);

        $logo = public_path('images/logo-cootranshuila.png');
        if (is_file($logo)) {
            $drawing = new Drawing();
            $drawing->setPath($logo);
            $drawing->setHeight(48);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        }

        $sheet->setCellValue('C1', 'Cooperativa de Transportadores del Huila');
        $sheet->setCellValue('C2', 'COOTRANSHUILA');
        $sheet->setCellValue('C3', $meta['sistema'] ?? 'SIPD');
        $sheet->setCellValue('F1', 'Uso interno');
        $sheet->setCellValue('F2', $meta['generado_el'] ?? '');
        $sheet->setCellValue('F3', $meta['generado_por'] ?? '');
        $sheet->getStyle('C1')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('006837'));
        $sheet->getStyle('C3')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('14532D'));
        $sheet->getStyle('F1')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('F1:F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $sheet->mergeCells('A5:F5');
        $sheet->setCellValue('A5', $layout['titulo'] ?? '');
        $this->styleExcelHeader($sheet, 'A5:F5');
        $sheet->getStyle('A5')->getFont()->setSize(13);

        $sheet->mergeCells('A6:F6');
        $sheet->setCellValue('A6', $layout['lead'] ?? '');
        $sheet->getStyle('A6')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
        $sheet->getStyle('A6')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(6)->setRowHeight(36);

        $sheet->setShowGridlines(true);
        $sheet->setPrintGridlines(true);

        $row = 8;
        if (!empty($layout['kpis'])) {
            $labels = [];
            $values = [];
            foreach ($layout['kpis'] as $kpi) {
                $labels[] = $kpi[0];
                $values[] = $kpi[1];
            }
            $kpiStart = $row;
            $kpiLast = $this->excelCol(count($labels));
            $sheet->fromArray([$labels], null, 'A' . $row);
            $this->styleExcelHeader($sheet, 'A' . $row . ':' . $kpiLast . $row);
            $row++;
            $sheet->fromArray([$values], null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':' . $kpiLast . $row)->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('006837'));
            $sheet->getStyle('A' . $row . ':' . $kpiLast . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->styleExcelGrid($sheet, 'A' . $kpiStart . ':' . $kpiLast . $row);
            $this->styleExcelHeader($sheet, 'A' . $kpiStart . ':' . $kpiLast . $kpiStart);
            $row += 2;
        }

        foreach ($layout['blocks'] as $block) {
            $colCount = max(1, count($block['headers'] ?? []));
            $last = $this->excelCol($colCount);
            if (!empty($block['title'])) {
                $sheet->mergeCells('A' . $row . ':' . $last . $row);
                $sheet->setCellValue('A' . $row, $block['title']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('006837'));
                $row++;
            }
            if (!empty($block['note'])) {
                $sheet->mergeCells('A' . $row . ':' . $last . $row);
                $sheet->setCellValue('A' . $row, $block['note']);
                $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
                $row++;
            }
            $headers = $block['headers'] ?? [];
            $rows = $block['rows'] ?? [];
            if ($rows === [] && !empty($block['empty'])) {
                $empty = [$block['empty']];
                while (count($empty) < count($headers)) {
                    $empty[] = '';
                }
                $rows = [$empty];
            }
            if ($headers) {
                $tableStart = $row;
                $sheet->fromArray([$headers], null, 'A' . $row);
                $this->styleExcelHeader($sheet, 'A' . $row . ':' . $last . $row);
                $row++;
                foreach ($rows as $dataRow) {
                    $sheet->fromArray([array_values($dataRow)], null, 'A' . $row);
                    $row++;
                }
                $tableEnd = $row - 1;
                $this->styleExcelGrid($sheet, 'A' . $tableStart . ':' . $last . $tableEnd);
                $this->styleExcelHeader($sheet, 'A' . $tableStart . ':' . $last . $tableStart);
                if (!$landscape && $tableEnd > $tableStart && $colCount >= 2) {
                    $sheet->getStyle('B' . ($tableStart + 1) . ':' . $last . $tableEnd)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
            $row++;
        }

        if (!empty($layout['footer'])) {
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('A' . $row, $layout['footer']);
            $sheet->getStyle('A' . $row)->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        }

        if (!empty($layout['chart_assets'])) {
            $chartSheet = $spreadsheet->createSheet();
            $chartSheet->setTitle('Gráficas');
            $chartSheet->mergeCells('A1:R1');
            $chartSheet->setCellValue('A1', 'Visualizaciones del informe');
            $this->styleExcelHeader($chartSheet, 'A1:R1');
            $chartSheet->getStyle('A1')->getFont()->setSize(15);
            $chartSheet->setShowGridlines(false);
            foreach (range('A', 'R') as $column) {
                $chartSheet->getColumnDimension($column)->setWidth(12);
            }
            $positions = [
                [['A2', 'J2'], ['A3', 'J3']],
                [['A17', 'J17'], ['A18', 'J18']],
                [['A32', 'J32'], ['A33', 'J33']],
            ];
            foreach ($layout['chart_assets'] as $index => $asset) {
                $row = intdiv($index, 2);
                $column = $index % 2;
                $titleCell = $positions[$row][0][$column];
                $titleColumn = preg_replace('/\d+$/', '', $titleCell);
                $titleRow = preg_replace('/^\D+/', '', $titleCell);
                $endColumn = $column === 0 ? 'I' : 'R';
                $chartSheet->mergeCells($titleColumn . $titleRow . ':' . $endColumn . $titleRow);
                $chartSheet->setCellValue($titleCell, $asset['title']);
                $chartSheet->getStyle($titleCell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('14532D'));

                $drawing = new Drawing();
                $drawing->setPath($asset['path']);
                $drawing->setName($asset['title']);
                $drawing->setDescription($asset['title']);
                $drawing->setHeight(210);
                $drawing->setCoordinates($positions[$row][1][$column]);
                $drawing->setWorksheet($chartSheet);
            }
            $spreadsheet->setActiveSheetIndex(0);
        }

        $this->autosize($sheet, 'M');
        return $spreadsheet;
    }

    private function downloadWordDocument(PhpWord $word, string $filename)
    {
        $path = tempnam(storage_path('app'), 'sipd-docx-');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function pct($n, int $total): string
    {
        return $total > 0 ? number_format(((int) $n) * 100 / $total, 1, ',', '.') . '%' : '—';
    }

    private function excelCol(int $index): string
    {
        $index = max(1, $index);
        $col = '';
        while ($index > 0) {
            $index--;
            $col = chr(65 + ($index % 26)) . $col;
            $index = intdiv($index, 26);
        }

        return $col;
    }

    private function periodBounds(Builder $query, ?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfMonth() : null;
        $end = $to ? Carbon::parse($to)->startOfMonth() : null;

        if (!$start || !$end) {
            $bounds = (clone $query)->selectRaw('MIN(created_at) AS min_date, MAX(created_at) AS max_date')->first();
            $start = $start ?: ($bounds && $bounds->min_date ? Carbon::parse($bounds->min_date)->startOfMonth() : null);
            $end = $end ?: ($bounds && $bounds->max_date ? Carbon::parse($bounds->max_date)->startOfMonth() : null);
        }

        return [$start, $end];
    }

    private function addWordTable($section, string $title, array $headers, array $rows): void
    {
        if ($title !== '') {
            $section->addText($title, ['bold' => true, 'size' => 12, 'color' => '006837']);
        }
        if ($rows === []) {
            $section->addText('Sin datos para este apartado.', ['italic' => true, 'size' => 10, 'color' => '64748B']);
            $section->addTextBreak();
            return;
        }
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 60,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $table->addRow();
        foreach ($headers as $header) {
            $cell = $table->addCell(null, ['bgColor' => '006837', 'valign' => 'center']);
            $cell->addText((string) $header, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($row as $value) {
                $table->addCell()->addText((string) $value, ['size' => 9]);
            }
        }
        $section->addTextBreak();
    }

    private function excelListSheet(Spreadsheet $spreadsheet, string $title, array $headers, array $items): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);
        $sheet->fromArray([$headers], null, 'A1');
        $row = 2;
        foreach ($items as $item) {
            $sheet->fromArray([[$item['label'] ?? '', $item['total'] ?? 0]], null, 'A' . $row++);
        }
        $this->styleExcelHeader($sheet, 'A1:' . chr(64 + count($headers)) . '1');
        $this->autosize($sheet, chr(64 + count($headers)));
    }

    private function styleExcelTitle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '006837']],
        ]);
    }

    private function styleExcelHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '006837'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '006837']],
            ],
        ]);
    }

    private function styleExcelGrid($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '94A3B8'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function autosize($sheet, string $lastCol): void
    {
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename)
    {
        $path = tempnam(storage_path('app'), 'sipd-xlsx-');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
