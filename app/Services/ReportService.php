<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;

class ReportService
{
    public function statistics(Builder $query, ?string $from, ?string $to): array
    {
        $filtered = $this->applyDateRange($query, $from, $to);
        $states = (clone $filtered)
            ->select('estado', DB::raw('COUNT(*) AS total'))
            ->groupBy('estado')
            ->orderBy('estado')
            ->get()
            ->mapWithKeys(function ($row) {
                return [(string) ($row->estado ?: 'Sin estado') => (int) $row->total];
            })
            ->all();

        $monthlyRows = (clone $filtered)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS period, estado, COUNT(*) AS total")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), 'estado')
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
                $monthly[] = [
                    'period' => $period,
                    'states' => $monthlyByPeriod[$period] ?? [],
                ];
            }
        }

        $pendingFaults = (clone $filtered)
            ->where('estado', 'Pendiente')
            ->selectRaw("COALESCE(NULLIF(tipo_falta, ''), 'Sin tipo de falta') AS tipo_falta, COUNT(*) AS total")
            ->groupBy(DB::raw("COALESCE(NULLIF(tipo_falta, ''), 'Sin tipo de falta')"))
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return ['label' => (string) $row->tipo_falta, 'total' => (int) $row->total];
            })
            ->values()
            ->all();

        return [
            'from' => $from,
            'to' => $to,
            'total' => array_sum($states),
            'states' => $states,
            'monthly' => $monthly,
            'pending_faults' => $pendingFaults,
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
        return Pdf::loadView('reports.global-pdf', ['data' => $data])
            ->setPaper('a4')
            ->download('reporte-global-sipd.pdf');
    }

    public function globalWord(array $data)
    {
        $word = new PhpWord();
        $section = $word->addSection();
        $section->addText('SIPD - REPORTE GLOBAL', ['bold' => true, 'size' => 16]);
        $section->addText('Periodo: ' . $this->periodLabel($data));
        $section->addTextBreak();
        $section->addText('Total de casos: ' . $data['total'], ['bold' => true]);
        $section->addTextBreak();
        $this->addWordRows($section, 'Distribucion por estado', $data['states']);
        $this->addWordRows($section, 'Casos pendientes por tipo de falta', collect($data['pending_faults'])->pluck('total', 'label')->all());
        $section->addTextBreak();
        $section->addText('Casos registrados por mes', ['bold' => true]);
        foreach ($data['monthly'] as $month) {
            $section->addText($month['period'] . ': ' . array_sum($month['states']) . ' casos');
        }

        $path = tempnam(storage_path('app'), 'sipd-global-');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);

        return response()->download($path, 'reporte-global-sipd.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function globalExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');
        $sheet->fromArray([
            ['REPORTE GLOBAL SIPD'],
            ['Periodo', $this->periodLabel($data)],
            ['Total de casos', $data['total']],
            [],
            ['Estado', 'Cantidad'],
        ], null, 'A1');
        $row = 6;
        foreach ($data['states'] as $state => $total) {
            $sheet->fromArray([[$state, $total]], null, 'A' . $row++);
        }

        $monthly = $spreadsheet->createSheet();
        $monthly->setTitle('Casos por mes');
        $monthly->fromArray([['Mes', 'Estado', 'Cantidad']], null, 'A1');
        $row = 2;
        foreach ($data['monthly'] as $month) {
            foreach ($month['states'] as $state => $total) {
                $monthly->fromArray([[$month['period'], $state, $total]], null, 'A' . $row++);
            }
        }

        $faults = $spreadsheet->createSheet();
        $faults->setTitle('Faltas pendientes');
        $faults->fromArray([['Tipo de falta', 'Cantidad']], null, 'A1');
        $row = 2;
        foreach ($data['pending_faults'] as $fault) {
            $faults->fromArray([[$fault['label'], $fault['total']]], null, 'A' . $row++);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'reporte-global-sipd.xlsx');
    }

    public function casesPdf($cases)
    {
        return Pdf::loadView('reports.cases-pdf', ['cases' => $cases])
            ->setPaper('a4', 'landscape')
            ->download('reporte-casos-sipd.pdf');
    }

    public function casesWord($cases)
    {
        $word = new PhpWord();
        $section = $word->addSection();
        $section->addText('SIPD - REPORTE DE CASOS', ['bold' => true, 'size' => 16]);
        $section->addText('Casos seleccionados: ' . $cases->count());
        $section->addTextBreak();
        foreach ($cases as $case) {
            $section->addText('PRO-' . str_pad($case->id, 3, '0', STR_PAD_LEFT) . ' - ' . $case->nombre, ['bold' => true]);
            $section->addText('Cedula: ' . ($case->cedula ?: 'Pendiente') . ' | Placa: ' . ($case->placa ?: 'Pendiente') . ' | Estado: ' . $case->estado);
            $section->addText('Tipo de falta: ' . ($case->tipo_falta ?: 'No especificada'));
            $section->addTextBreak();
        }

        $path = tempnam(storage_path('app'), 'sipd-cases-');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);

        return response()->download($path, 'reporte-casos-sipd.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function casesExcel($cases)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Casos');
        $sheet->fromArray([['Proceso', 'Conductor', 'Cedula', 'Placa', 'Tipo de falta', 'Estado', 'Fecha', 'Modalidad']], null, 'A1');
        $row = 2;
        foreach ($cases as $case) {
            $sheet->fromArray([[
                'PRO-' . str_pad($case->id, 3, '0', STR_PAD_LEFT),
                $case->nombre,
                $case->cedula,
                $case->placa,
                $case->tipo_falta,
                $case->estado,
                $case->created_at ? $case->created_at->format('Y-m-d') : null,
                $case->modalidad,
            ]], null, 'A' . $row++);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'reporte-casos-sipd.xlsx');
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

    private function addWordRows($section, string $title, array $rows): void
    {
        $section->addText($title, ['bold' => true]);
        foreach ($rows as $label => $total) {
            $section->addText($label . ': ' . $total);
        }
    }

    private function periodLabel(array $data): string
    {
        return ($data['from'] ?: 'Inicio') . ' a ' . ($data['to'] ?: 'Actualidad');
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
