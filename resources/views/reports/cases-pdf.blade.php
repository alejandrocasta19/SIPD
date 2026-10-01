@php
    $meta = $meta ?? [];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 16mm 12mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }
        .mast { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .mast td { vertical-align: middle; border: 0; padding: 0; }
        .mast-logo { width: 70px; }
        .mast-logo img { height: 42px; }
        .org { font-size: 7px; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
        .brand { font-size: 14px; font-weight: 700; color: #006837; }
        .sys { font-size: 8px; color: #14532d; }
        .mast-meta { text-align: right; font-size: 8px; color: #475569; width: 170px; }
        .band { background: #006837; color: #fff; padding: 7px 10px; font-size: 12px; font-weight: 700; }
        .lead { color: #475569; margin: 8px 0 10px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.grid th { background: #006837; color: #fff; font-size: 7.5px; }
        .note { font-size: 8px; color: #64748b; margin-top: 10px; }
        h2 { font-size: 10px; color: #006837; margin: 14px 0 6px; }
    </style>
</head>
<body>
@include('reports.partials.encabezado', ['meta' => $meta, 'titulo' => $meta['titulo'] ?? 'Relación de expedientes seleccionados'])

<p class="lead">
    Listado operativo de {{ $cases->count() }} expediente{{ $cases->count() === 1 ? '' : 's' }}
    del régimen disciplinario interno de Cootranshuila.
    Los datos corresponden a la selección hecha en SIPD al momento de generar este documento.
</p>

<table class="grid">
    <thead>
        <tr>
            <th>Proceso</th>
            <th>Radicado</th>
            <th>Trabajador</th>
            <th>Cédula</th>
            <th>Placa</th>
            <th>Ruta</th>
            <th>Cargo</th>
            <th>Tipo de falta</th>
            <th>Estado</th>
            <th>Fecha falta</th>
            <th>Apertura</th>
            <th>RH</th>
            <th>Anexos</th>
        </tr>
    </thead>
    <tbody>
    @foreach($cases as $case)
        <tr>
            <td>{{ $case->codigoProceso() }}</td>
            <td>{{ $case->numeroRadicado() }}</td>
            <td>{{ $case->nombre }}</td>
            <td>{{ $case->cedula ?: 'Pendiente' }}</td>
            <td>{{ $case->placa ?: '—' }}</td>
            <td>{{ $case->ruta ?: '—' }}</td>
            <td>{{ $case->modalidad ?: '—' }}</td>
            <td>{{ $case->tipo_falta ?: 'No especificada' }}</td>
            <td>{{ $case->estado }}</td>
            <td>{{ $case->fecha_falta ? $case->fecha_falta->format('d/m/Y') : '—' }}</td>
            <td>{{ $case->created_at ? $case->created_at->format('d/m/Y') : '—' }}</td>
            <td>{{ optional($case->user)->name ?: 'Sin asignar' }}</td>
            <td>{{ (int) ($case->anexos_count ?? 0) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@if($cases->contains(fn ($c) => filled($c->decision_final) || filled($c->observacion) || filled($c->descargos)))
<h2>Decisiones, descargos y observaciones</h2>
<table class="grid">
    <tr><th>Proceso</th><th>Descargos</th><th>Decisión / observación</th></tr>
    @foreach($cases as $case)
        @if(filled($case->decision_final) || filled($case->observacion) || filled($case->descargos))
        <tr>
            <td>{{ $case->codigoProceso() }}</td>
            <td>{{ $case->descargos ? \Illuminate\Support\Str::limit(strip_tags($case->descargos), 180) : '—' }}</td>
            <td>{{ $case->decision_final ?: ($case->observacion ? \Illuminate\Support\Str::limit($case->observacion, 180) : '—') }}</td>
        </tr>
        @endif
    @endforeach
</table>
@endif

<p class="note">
    {{ $meta['uso'] ?? 'Documento de uso interno de Coordinación de RH. No divulgar fuera de Cootranshuila.' }}
</p>
</body>
</html>
