@php
    $meta = $data['meta'] ?? [];
    $anexos = $data['anexos'] ?? [];
    $reinc = $data['reincidencias'] ?? [];
    $kpis = $data['kpis'] ?? [];
    $total = (int) ($data['total'] ?? 0);
    $pct = function ($n) use ($total) {
        return $total > 0 ? number_format(((int) $n) * 100 / $total, 1, ',', '.') . '%' : '—';
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 22mm 16mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .mast { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .mast td { vertical-align: middle; border: 0; padding: 0; }
        .mast-logo { width: 78px; }
        .mast-logo img { height: 48px; }
        .org { font-size: 8px; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
        .brand { font-size: 16px; font-weight: 700; color: #006837; line-height: 1.1; }
        .sys { font-size: 9px; color: #14532d; margin-top: 2px; }
        .mast-meta { text-align: right; font-size: 8px; color: #475569; width: 160px; }
        .band { background: #006837; color: #fff; padding: 8px 10px; font-size: 13px; font-weight: 700; }
        .lead { color: #475569; margin: 8px 0 12px; font-size: 10px; }
        .kpis { width: 100%; border-collapse: collapse; margin: 0 0 14px; }
        .kpis td { width: 25%; border: 1px solid #d1d5db; padding: 8px; text-align: center; background: #f8fafc; }
        .kpis small { display: block; font-size: 8px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .kpis strong { display: block; font-size: 16px; color: #006837; margin-top: 2px; }
        h2 { font-size: 11px; color: #006837; border-bottom: 1px solid #cfead9; padding-bottom: 3px; margin: 16px 0 6px; text-transform: uppercase; letter-spacing: .04em; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.grid th, table.grid td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        table.grid th { background: #006837; color: #fff; font-size: 9px; }
        table.grid td.num { text-align: right; white-space: nowrap; }
        .note { font-size: 8px; color: #64748b; margin: 0 0 10px; }
        .chart-grid { width:100%; border-collapse:collapse; margin:0 0 8px; page-break-inside:avoid; }
        .chart-grid td { width:50%; border:0; padding:4px 5px 8px; vertical-align:top; page-break-inside:avoid; }
        .chart-grid h3 { color:#14532d; font-size:9px; margin:0 0 3px; }
        .chart-grid img { width:100%; height:auto; }
        .foot { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
</head>
<body>
@include('reports.partials.encabezado', ['data' => $data, 'titulo' => $meta['titulo'] ?? null])

<p class="lead">
    Este informe consolida el comportamiento de los procesos disciplinarios de Cootranshuila
    en el período <strong>{{ $meta['periodo'] ?? (($data['from'] ?: 'Inicio').' a '.($data['to'] ?: 'Actualidad')) }}</strong>.
    Incluye estados del expediente, faltas más frecuentes, cargos involucrados, rutas,
    reincidencias y el avance documental de anexos.
    @if(!empty($meta['filtro']))
        Filtro aplicado: <strong>{{ $meta['filtro'] }}</strong>.
    @endif
</p>

<table class="kpis">
    <tr>
        <td><small>Total de casos</small><strong>{{ $total }}</strong></td>
        <td><small>Pendientes</small><strong>{{ $kpis['pendientes'] ?? 0 }}</strong></td>
        <td><small>En proceso</small><strong>{{ $kpis['en_proceso'] ?? 0 }}</strong></td>
        <td><small>Finalizados</small><strong>{{ $kpis['finalizados'] ?? 0 }}</strong></td>
    </tr>
</table>

@if(!empty($data['charts']))
<h2>Visualizaciones del período</h2>
<table class="chart-grid">
    @foreach(array_chunk($data['charts'], 2) as $chartRow)
        <tr>
            @foreach($chartRow as $chart)
                <td>
                    <h3>{{ $chart['title'] }}</h3>
                    <img src="{{ $chart['src'] }}" alt="{{ $chart['title'] }}">
                </td>
            @endforeach
            @if(count($chartRow) === 1)<td></td>@endif
        </tr>
    @endforeach
</table>
@endif

<h2>1. Distribución por estado</h2>
<p class="note">Estado actual de cada expediente en el período.</p>
<table class="grid">
    <tr><th>Estado</th><th>Cantidad</th><th>Participación</th></tr>
    @forelse(($data['states'] ?? []) as $state => $count)
        <tr><td>{{ $state }}</td><td class="num">{{ $count }}</td><td class="num">{{ $pct($count) }}</td></tr>
    @empty
        <tr><td colspan="3">Sin procesos en el período.</td></tr>
    @endforelse
</table>

<h2>2. Anexos del expediente</h2>
<p class="note">Escaneos firmados por gerencia y documentos de un caso anterior.</p>
<table class="grid">
    <tr><th>Concepto</th><th>Cantidad</th></tr>
    <tr><td>Total de anexos</td><td class="num">{{ $anexos['total'] ?? 0 }}</td></tr>
    <tr><td>Archivo previo</td><td class="num">{{ $anexos['archivo_previo'] ?? 0 }}</td></tr>
    <tr><td>Firmados</td><td class="num">{{ $anexos['firmado'] ?? 0 }}</td></tr>
</table>

<h2>3. Volumen mensual</h2>
<p class="note">Casos registrados en cada mes.</p>
<table class="grid">
    <tr><th>Mes</th><th>Casos</th></tr>
    @forelse(($data['monthly'] ?? []) as $month)
        @php $st = $month['states'] ?? []; @endphp
        <tr>
            <td>{{ $month['period'] }}</td>
            <td class="num">{{ $month['total'] ?? array_sum($st) }}</td>
        </tr>
    @empty
        <tr><td colspan="2">Sin movimiento mensual.</td></tr>
    @endforelse
</table>

<h2>4. Modalidad y cargo</h2>
<p class="note">Modalidad del proceso y cargo del trabajador.</p>
<table class="grid">
    <tr><th>#</th><th>Modalidad</th><th>Casos</th><th>Participación</th></tr>
    @forelse(($data['by_modalidad'] ?? []) as $i => $row)
        <tr><td class="num">{{ $i + 1 }}</td><td>{{ $row['label'] }}</td><td class="num">{{ $row['total'] }}</td><td class="num">{{ $pct($row['total']) }}</td></tr>
    @empty
        <tr><td colspan="4">Sin cargos registrados.</td></tr>
    @endforelse
</table>

<h2>5. Tipos de falta</h2>
<table class="grid">
    <tr><th>#</th><th>Tipo de falta</th><th>Casos</th><th>Participación</th></tr>
    @forelse(($data['pending_faults'] ?? []) as $i => $fault)
        <tr><td class="num">{{ $i + 1 }}</td><td>{{ $fault['label'] }}</td><td class="num">{{ $fault['total'] }}</td><td class="num">{{ $pct($fault['total']) }}</td></tr>
    @empty
        <tr><td colspan="4">Sin tipos de falta.</td></tr>
    @endforelse
</table>

<h2>6. Rutas</h2>
<table class="grid">
    <tr><th>Ruta</th><th>Casos</th><th>Participación</th></tr>
    @forelse(($data['by_ruta'] ?? []) as $row)
        <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['total'] }}</td><td class="num">{{ $pct($row['total']) }}</td></tr>
    @empty
        <tr><td colspan="3">Sin rutas registradas.</td></tr>
    @endforelse
</table>

<h2>7. Descargos y seguimiento</h2>
<table class="grid">
    <tr><th>Concepto</th><th>Cantidad</th></tr>
    <tr><td>Con descargos registrados</td><td class="num">{{ $data['descargos']['con'] ?? 0 }}</td></tr>
    <tr><td>Sin descargos</td><td class="num">{{ $data['descargos']['sin'] ?? 0 }}</td></tr>
    <tr><td>Expedientes sin responsable de RH</td><td class="num">{{ $kpis['sin_responsable'] ?? 0 }}</td></tr>
    <tr><td>Duración promedio de casos cerrados</td><td class="num">{{ isset($kpis['prom_dias']) && $kpis['prom_dias'] !== null ? $kpis['prom_dias'].' días' : '—' }}</td></tr>
</table>

<h2>8. Reincidencias</h2>
<p class="note">Trabajadores identificados por cédula. Reincidente: más de un proceso en el período.</p>
<table class="grid">
    <tr><th>Concepto</th><th>Cantidad</th></tr>
    <tr><td>Primera vez</td><td class="num">{{ $reinc['primera_vez'] ?? 0 }}</td></tr>
    <tr><td>Reincidentes</td><td class="num">{{ $reinc['reincidentes'] ?? 0 }}</td></tr>
</table>
@if(!empty($data['top_reincidentes']))
<table class="grid" style="margin-top:8px;">
    <tr><th>#</th><th>Trabajador</th><th>Cédula</th><th>Procesos</th></tr>
    @foreach($data['top_reincidentes'] as $i => $row)
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td>{{ $row['nombre'] }}</td>
            <td>{{ $row['cedula'] }}</td>
            <td class="num">{{ $row['total'] }}</td>
        </tr>
    @endforeach
</table>
@endif

<p class="note" style="margin-top:18px;">
    {{ $meta['uso'] ?? 'Documento de uso interno de Coordinación de RH. No divulgar fuera de Cootranshuila.' }}
    Generado por {{ $meta['generado_por'] ?? 'SIPD' }}{{ !empty($meta['equipo']) ? ' · '.$meta['equipo'] : '' }}.
</p>
</body>
</html>
