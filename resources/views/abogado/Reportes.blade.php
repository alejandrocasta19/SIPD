@extends('layouts.master')

@section('styles')
<style>
    /* ── HEADER ──────────────────────────────────── */
    .rp-actions { display:flex; gap:6px; flex-wrap:wrap; }
    .rbtn { border:0; border-radius:7px; padding:7px 12px; font-weight:700; font-size:11px; cursor:pointer; text-decoration:none; display:inline-flex; gap:5px; align-items:center; transition:opacity .15s; }
    .rbtn:hover { opacity:.82; }
    .rbtn.g   { background:var(--cth-green-text); color:#fff; }
    .rbtn.sl  { background:#f1f5f9; color:#334155; }
    .rbtn.pdf { background:#fee2e2; color:#b91c1c; }
    .rbtn.doc { background:#dbeafe; color:#1d4ed8; }
    .rbtn.xls { background:#dcfce7; color:var(--cth-green-text); }

    /* ── FILTERS ─────────────────────────────────── */
    .rp-filters { background:#fff; border: 1px solid var(--cth-border); border-radius:10px; padding:10px 14px; margin-bottom:14px; display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end; }
    .rf { min-width:140px; flex:1; }
    .rf label { display:block; color:#64748b; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px; }
    .rf input  { width:100%; height:32px; border: 1px solid var(--cth-border); border-radius:7px; padding:0 9px; font-size:12px; }
    .rp-error  { display:none; background:#fef2f2; color:#b91c1c; border-radius:8px; padding:8px 12px; margin-bottom:12px; font-size:12px; }

    /* ── KPI CARDS ───────────────────────────────── */
    .kpi-row   { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .kpi-card  { background:#fff; border: 1px solid var(--cth-border); border-radius:10px; padding:12px 14px; min-width:0; }
    .kpi-card small  { color:#64748b; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; display:block; }
    .kpi-card strong { font-size:22px; font-weight:800; color:#0f172a; display:block; margin-top:2px; line-height:1; }

    /* ── CHARTS ──────────────────────────────────── */
    .charts-wrap { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; margin-bottom:20px; }
    .ch-card     { background:linear-gradient(180deg,#fff 0%,#fbfdff 100%); border:1px solid #dbe3ed; border-radius:14px; padding:13px 15px; min-width:0; min-height:245px; display:flex; flex-direction:column; box-shadow:0 4px 14px rgba(15,23,42,.045); }
    .ch-card h3  { margin:0 0 3px; font-size:14px; font-weight:800; color:#0f172a; }
    .ch-card p   { color:#64748b; font-size:11px; margin:0 0 12px; }
    .ch-wrap     { position:relative; height:170px; flex:0 0 170px; min-height:0; }
    .ch-empty    { display:none; color:#94a3b8; text-align:center; padding:48px 8px; font-size:12px; }
    .top-more    { border:1px solid #dbeafe; background:#f8fafc; color:#1d4ed8; border-radius:8px; padding:7px 10px; font-size:11px; font-weight:700; cursor:pointer; display:none; width:100%; margin-top:9px; justify-content:center; flex-shrink:0; }
    .top-more:hover { background:#eff6ff; }
    .top-list    { list-style:none; margin:0; padding:0; flex:1; min-height:0; overflow:hidden; }
    .top-list li { padding:4px 0; border-bottom: 1px solid var(--cth-line); }
    .top-list li:last-child { border-bottom:0; }
    .top-row     { display:flex; align-items:center; gap:8px; }
    .top-rank    { width:18px; height:18px; border-radius:50%; background:#f1f5f9; color:#475569; font-size:9px; font-weight:800; display:grid; place-items:center; flex-shrink:0; }
    .top-name    { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#0f172a; font-size:12px; font-weight:600; }
    .top-n       { font-weight:800; color:#0f172a; font-size:12px; flex-shrink:0; }
    .top-bar     { height:3px; background:#e2e8f0; border-radius:999px; margin-top:4px; }
    .top-bar > span { display:block; height:100%; border-radius:999px; }
    .hist-kpis   { grid-template-columns:repeat(5, minmax(0,1fr)); }

    .rp-pop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:80; place-items:center; padding:16px; }
    .rp-pop.open { display:grid; }
    .rp-pop-box { background:#fff; border-radius:12px; width:min(440px,100%); max-height:80vh; overflow:auto; padding:16px 18px; box-shadow:0 20px 40px rgba(15,23,42,.18); }
    .rp-pop-box h3 { margin:0; font-size:14px; font-weight:800; color:#0f172a; }
    .rp-pop-box p { margin:2px 0 12px; color:#64748b; font-size:12px; }
    .rp-pop-close { border:0; background:#f1f5f9; color:#334155; border-radius:8px; padding:7px 12px; font-size:12px; font-weight:700; cursor:pointer; margin-top:12px; width:100%; }

    /* ── DIVIDER ─────────────────────────────────── */
    .sec-div { display:flex; align-items:center; gap:10px; margin:16px 0 12px; }
    .sec-div h2   { margin:0; font-size:13px; font-weight:800; color:#0f172a; white-space:nowrap; }
    .sec-div span { color:#94a3b8; font-size:11px; white-space:nowrap; }
    .sec-div hr   { flex:1; border:none; border-top: 1px solid var(--cth-line); }

    /* ── TABLE TOOLBAR ───────────────────────────── */
    .tbl-toolbar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
    .tbl-toolbar label { font-size:12px; color:#475569; font-weight:600; display:flex; align-items:center; gap:6px; cursor:pointer; }
    .btn-grp { display:flex; gap:5px; flex-wrap:wrap; }

    /* ── TABLE ───────────────────────────────────── */
    .case-tbl { background:#fff; border: 1px solid var(--cth-border); border-radius:10px; overflow:auto; }
    .case-tbl table { width:100%; min-width:720px; border-collapse:collapse; font-size:11.5px; }
    .case-tbl thead tr { background:#f8fafc; }
    .case-tbl th { padding:6px 9px; border:1px solid var(--cth-border); border-bottom: 2px solid #e2e8f0; text-align:left; color:#64748b; font-size:9.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
    .case-tbl td { padding:5px 9px; border:1px solid var(--cth-border); color:#334155; vertical-align:middle; background:#fff; }
    .case-tbl tr:hover td { background:#f8fafc; }
    .case-tbl tr.st-pendiente td:first-child  { border-left: 3px solid #f59e0b; }
    .case-tbl tr.st-en-proceso td:first-child  { border-left: 3px solid #3b82f6; }
    .case-tbl tr.st-sancionado td:first-child  { border-left: 3px solid #f43f5e; }
    .case-tbl tr.st-archivado td:first-child   { border-left: 3px solid #94a3b8; }
    .proc-lnk { color:var(--cth-green-text); font-weight:700; text-decoration:none; font-size:10.5px; font-family:monospace; }
    .case-tbl td b { font-weight:600; color:#0f172a; }
    .act-lnk  { width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px; transition: all .15s; }
    .act-lnk:hover { background: #ecfdf5; color: var(--cth-green-text); }
    .empty-c  { text-align:center; padding:28px; color:#94a3b8; font-size:12px; }
    .tbl-pager{ display:flex; justify-content:space-between; align-items:center; padding:8px 10px; color:#64748b; font-size:11px; border-top:1px solid #f1f5f9; }

    /* ── BADGES ──────────────────────────────────── */
    .sb { padding:2px 7px; border-radius:999px; font-size:9.5px; font-weight:700; border:1px solid transparent; letter-spacing:.03em; white-space:nowrap; display:inline-block; }
    .sb.pendiente  { background:#fef9c3; color:#a16207; border-color:#fef08a; }
    .sb.en-proceso { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .sb.sancionado { background:#fef2f2; color:#b91c1c; border-color:#fca5a5; }
    .sb.archivado  { background:#f1f5f9; color:#334155; border-color:#e2e8f0; }

    @media (max-width:1200px) {
        .charts-wrap { grid-template-columns:1fr 1fr; gap:12px; }
        .hist-kpis { grid-template-columns:repeat(3, minmax(0,1fr)); }
    }
    @media (max-width:900px) {
        .kpi-row { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .charts-wrap { grid-template-columns:1fr; }
        .ch-card { min-height:235px; }
        .ch-wrap { height:165px; flex-basis:165px; }
        .hist-kpis { grid-template-columns:repeat(2, minmax(0,1fr)); }
    }
    @media (max-width:560px) {
        .rf { width:100%; min-width:100%; }
    }
</style>
@endsection

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Estadísticas y reportes</h1>
            <p>Panorama de procesos disciplinarios y de los anexos del expediente.</p>
        </div>
        @if(auth()->user()->puede('exportar_reportes'))
        <div class="proc-head-side rp-actions">
            <form method="POST" action="{{ route('abogado.reportes.global', 'pdf') }}" class="export-form">
                @csrf<input type="hidden" name="desde"><input type="hidden" name="hasta"><input type="hidden" name="q">
                <button class="rbtn pdf" type="submit"><i class="fas fa-file-pdf"></i> PDF</button>
            </form>
            <form method="POST" action="{{ route('abogado.reportes.global', 'word') }}" class="export-form">
                @csrf<input type="hidden" name="desde"><input type="hidden" name="hasta"><input type="hidden" name="q">
                <button class="rbtn doc" type="submit"><i class="fas fa-file-word"></i> Word</button>
            </form>
            <form method="POST" action="{{ route('abogado.reportes.global', 'excel') }}" class="export-form">
                @csrf<input type="hidden" name="desde"><input type="hidden" name="hasta"><input type="hidden" name="q">
                <button class="rbtn xls" type="submit"><i class="fas fa-file-excel"></i> Excel</button>
            </form>
        </div>
        @endif
    </div>
@endsection

@section('content')

{{-- FILTROS --}}
<form class="rp-filters" method="GET" action="{{ route('abogado.reportes') }}" id="report-filters">
    <div class="rf"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}"></div>
    <div class="rf"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}"></div>
    <div class="rf"><label for="q">Buscar</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Trabajador, cédula, placa…"></div>
    <button class="rbtn g"  type="submit"><i class="fas fa-search"></i> Filtrar</button>
    <a href="{{ route('abogado.reportes') }}" class="rbtn sl"><i class="fas fa-undo"></i> Limpiar</a>
</form>

<div class="rp-error" id="report-error"></div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi-card"><small>Total casos</small><strong id="card-total">0</strong></div>
    <div class="kpi-card"><small>Pendientes</small><strong id="card-pendientes">0</strong></div>
    <div class="kpi-card"><small>En proceso</small><strong id="card-proceso">0</strong></div>
    <div class="kpi-card"><small>Finalizados</small><strong id="card-finalizados">0</strong></div>
</div>
<div class="kpi-row">
    <div class="kpi-card" style="border-left:3px solid var(--cth-green-text);"><small>Anexos</small><strong id="card-anexos">0</strong></div>
    <div class="kpi-card"><small>Archivo previo</small><strong id="card-anexos-previo">0</strong></div>
    <div class="kpi-card" style="border-left:3px solid var(--cth-green-bright);"><small>Firmados</small><strong id="card-anexos-firmado">0</strong></div>
</div>

{{-- CHARTS --}}
<div class="charts-wrap">
    <div class="ch-card">
        <h3>Casos diarios</h3><p>Registros por día en el período.</p>
        <div class="ch-wrap" id="daily-wrap">
            <canvas id="daily-chart"></canvas>
            <div class="ch-empty">Sin datos diarios.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Casos semanales</h3><p>Tendencia de registros por semana.</p>
        <div class="ch-wrap" id="weekly-wrap">
            <canvas id="weekly-chart"></canvas>
            <div class="ch-empty">Sin datos semanales.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Casos por mes</h3><p>Volumen registrado en cada mes.</p>
        <div class="ch-wrap" id="monthly-wrap">
            <canvas id="monthly-chart"></canvas>
            <div class="ch-empty">Sin datos mensuales.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Estado Global</h3><p>Proporción de procesos por su estatus actual.</p>
        <div class="ch-wrap" id="status-wrap">
            <canvas id="status-chart"></canvas>
            <div class="ch-empty">Sin casos registrados.</div>
        </div>
    </div>

    <div class="ch-card">
        <h3>Modalidad y cargo</h3><p>Distribución de los principales cargos por número de procesos.</p>
        <div class="ch-wrap" id="cargo-wrap">
            <canvas id="cargo-chart"></canvas>
            <div class="ch-empty">Sin cargos registrados.</div>
        </div>
        <button type="button" class="top-more" id="cargo-more">Ampliar vista</button>
    </div>
    <div class="ch-card">
        <h3>Tipo de falta</h3><p>Faltas con más procesos dentro del período seleccionado.</p>
        <div class="ch-wrap" id="fault-wrap">
            <canvas id="fault-chart"></canvas>
            <div class="ch-empty">Sin faltas.</div>
        </div>
        <button type="button" class="top-more" id="fault-more">Ampliar vista</button>
    </div>
</div>

{{-- DIVIDER --}}
<div class="sec-div">
    <h2><i class="fas fa-table" style="color:var(--cth-green-text);"></i> Detalle de Casos</h2>
    <span>{{ $casos->total() }} proceso{{ $casos->total() === 1 ? '' : 's' }}</span>
    <hr>
</div>

{{-- FORM TABLA --}}
<form id="cases-form" method="POST" action="{{ url('/abogado/reportes/casos/pdf') }}">
    @csrf

    <div class="tbl-toolbar">
        <label><input type="checkbox" id="select-all"> Seleccionar todos</label>
        @if(auth()->user()->puede('exportar_reportes'))
        <div class="btn-grp">
            <button class="rbtn pdf" data-format="pdf"   type="submit"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="rbtn doc" data-format="word"  type="submit"><i class="fas fa-file-word"></i> Word</button>
            <button class="rbtn xls" data-format="excel" type="submit"><i class="fas fa-file-excel"></i> Excel</button>
        </div>
        @endif
    </div>

    <div class="case-tbl">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Proceso</th>
                    <th>Trabajador</th>
                    <th>Cédula</th>
                    <th>Tipo de Falta</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Modalidad</th>
                    <th>Anexos</th>
                    <th style="text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($casos as $caso)
                @php $stCaso = 'st-' . strtolower(str_replace(' ', '-', $caso->estadoVisible())); @endphp
                <tr class="{{ $stCaso }}">
                    <td style="width:26px;"><input type="checkbox" name="ids[]" value="{{ $caso->id }}" class="case-check"></td>
                    <td><a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="proc-lnk">PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</a></td>
                    <td><b>{{ $caso->nombre }}</b></td>
                    <td>{{ $caso->cedula   ?: '—' }}</td>
                    <td>{{ $caso->tipo_falta ?: '—' }}</td>
                    <td><span class="sb {{ strtolower(str_replace(' ', '-', $caso->estadoVisible())) }}">{{ $caso->estadoVisible() }}</span></td>
                    <td>{{ $caso->created_at ? $caso->created_at->format('d/m/Y') : '—' }}</td>
                    <td>{{ \App\Support\Modalidades::etiquetaCaso($caso->modalidad, $caso->cargo) }}</td>
                    <td style="text-align:center;">{{ $caso->anexos_count ?: '—' }}</td>
                    <td style="text-align:right;"><a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="act-lnk" title="Ver Expediente"><i class="fas fa-eye"></i></a></td>
                </tr>
            @empty
                <tr><td class="empty-c" colspan="10"><i class="fas fa-search" style="display:block;font-size:18px;margin-bottom:6px;opacity:.35;"></i>No hay casos que coincidan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
        @include('partials.paginacion', ['paginador' => $casos])

{{-- ═══════════════════════════════════════════════ --}}
{{-- HISTORIAL DE CASOS CERRADOS                     --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="sec-div" style="margin-top:24px;">
    <h2><i class="fas fa-archive" style="color:var(--cth-green-text);"></i> Historial — Casos Cerrados</h2>
    <span>{{ $hStats['total'] }} registro{{ $hStats['total'] === 1 ? '' : 's' }}</span>
    <hr>
</div>

{{-- Mini KPIs del historial --}}
<div class="kpi-row hist-kpis">
    <div class="kpi-card" style="border-left:3px solid var(--cth-green-text);">
        <small>Total cerrados</small>
        <strong style="font-size:20px;">{{ $hStats['total'] }}</strong>
    </div>
    <div class="kpi-card" style="border-left:3px solid #b91c1c;">
        <small>Sancionados</small>
        <strong style="font-size:20px;color:#b91c1c;">{{ $hStats['sancionados'] }}</strong>
    </div>
    <div class="kpi-card" style="border-left:3px solid #64748b;">
        <small>Archivados</small>
        <strong style="font-size:20px;color:#64748b;">{{ $hStats['archivados'] }}</strong>
    </div>
    <div class="kpi-card" style="border-left:3px solid #f59e0b;">
        <small>Duración promedio</small>
        <strong style="font-size:20px;color:#92400e;">
            {{ $hStats['prom_dias'] ? round($hStats['prom_dias']).' días' : '—' }}
        </strong>
    </div>
    <div class="kpi-card" style="border-left:3px solid var(--cth-green-bright);">
        <small>Anexos</small>
        <strong style="font-size:20px;">{{ $hStats['anexos'] ?: '—' }}</strong>
    </div>
</div>

{{-- Tabla historial --}}
<div class="case-tbl">
    <table>
        <thead>
            <tr>
                <th>Proceso</th>
                <th>Trabajador</th>
                <th>Cédula</th>
                <th>Placa</th>
                <th>Modalidad</th>
                <th>Tipo de Falta</th>
                <th>Resultado</th>
                <th>Responsable</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th>Duración</th>
                <th>Anexos</th>
                <th>Decisión / Observación</th>
                <th style="text-align:right;"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($historial as $h)
            @php $stH = 'st-' . strtolower(str_replace(' ', '-', $h->estado)); @endphp
            <tr class="{{ $stH }}">
                <td><a href="{{ route('abogado.detalleproceso', $h->id) }}" class="proc-lnk">PRO-{{ str_pad($h->id, 3, '0', STR_PAD_LEFT) }}</a></td>
                <td><b>{{ $h->nombre }}</b></td>
                <td>{{ $h->cedula   ?: '—' }}</td>
                <td>{{ \App\Support\Modalidades::textoPlaca($h->modalidad, $h->placa) }}</td>
                <td>{{ \App\Support\Modalidades::etiquetaCaso($h->modalidad, $h->cargo) }}</td>
                <td>{{ $h->tipo_falta ?: '—' }}</td>
                <td>
                    <span class="sb {{ strtolower(str_replace(' ', '-', $h->estado)) }}">{{ $h->estado }}</span>
                </td>
                <td style="color:#475569;">{{ $h->abogado }}</td>
                <td style="white-space:nowrap;">{{ $h->fecha_inicio }}</td>
                <td style="white-space:nowrap;">{{ $h->fecha_cierre }}</td>
                <td style="text-align:center;">
                    @if($h->duracion_dias !== null)
                        <span class="dias {{ $h->duracion_dias > 30 ? 'late' : 'ok' }}">
                            {{ $h->duracion_dias }}d
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td style="text-align:center;">{{ $h->anexos ?: '—' }}</td>
                <td style="max-width:220px;color:#475569;font-size:11px;">
                    {{ $h->decision ? Str::limit($h->decision, 80) : '—' }}
                </td>
                <td style="text-align:right;">
                    <a href="{{ route('abogado.detalleproceso', $h->id) }}" class="act-lnk" title="Ver Expediente">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td class="empty-c" colspan="14">
                    <i class="fas fa-archive" style="display:block;font-size:18px;margin-bottom:6px;opacity:.35;"></i>
                    No hay casos archivados ni sancionados aún.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    @include('partials.paginacion', ['paginador' => $historial])
</div>

<div class="rp-pop" id="rank-pop" role="dialog" aria-modal="true">
    <div class="rp-pop-box">
        <h3 id="rank-pop-title"></h3>
        <p id="rank-pop-sub"></p>
        <ol class="top-list" id="rank-pop-list"></ol>
        <button type="button" class="rp-pop-close" id="rank-pop-close">Cerrar</button>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var endpoint = @json(route('abogado.reportes.datos'));
    var charts = {};
    var colors = ['var(--cth-green-text)','#2563eb','#f59e0b','#dc2626','#64748b','#7c3aed','#0891b2'];
    var errBox = document.getElementById('report-error');

    function syncForms() {
        var p = new URLSearchParams(window.location.search);
        document.querySelectorAll('.export-form').forEach(function (f) {
            ['desde','hasta','q'].forEach(function(k){ var el=f.querySelector('[name="'+k+'"]'); if(el) el.value=p.get(k)||''; });
        });
    }

    function showEmpty(id, empty) {
        var w = document.getElementById(id);
        var canvas = w.querySelector('canvas');
        if (canvas) canvas.style.display = empty ? 'none' : 'block';
        w.querySelector('.ch-empty').style.display = empty ? 'block' : 'none';
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function rankItemsHtml(items, color) {
        var max = 1;
        items.forEach(function (it) { if (it.total > max) max = it.total; });
        return items.map(function (it, i) {
            var pct = Math.max(6, Math.round((it.total / max) * 100));
            return '<li><div class="top-row"><span class="top-rank">' + (i + 1) + '</span>'
                + '<span class="top-name" title="' + esc(it.label) + '">' + esc(it.label) + '</span>'
                + '<span class="top-n">' + it.total + '</span></div>'
                + '<div class="top-bar"><span style="width:' + pct + '%;background:' + color + ';"></span></div></li>';
        }).join('');
    }

    function renderTopList(listId, emptyWrapId, btnId, items, color, modalTitle, modalSub) {
        var chartId = listId.replace('-list', '-chart');
        var canvas = document.getElementById(chartId);
        var btn = document.getElementById(btnId);
        var all = items || [];
        if (charts[chartId]) charts[chartId].destroy();
        var chartItems = all.slice(0, 8);
        if (chartItems.length) {
            charts[chartId] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: chartItems.map(function (item) { return item.label; }),
                    datasets: [{
                        label: 'Casos',
                        data: chartItems.map(function (item) { return item.total; }),
                        backgroundColor: color,
                        borderRadius: 6,
                        maxBarThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function (ctx) { return ' ' + ctx.raw + ' caso(s)'; } } }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148,163,184,.18)' },
                            ticks: { precision: 0, font: { size: 10 } }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                autoSkip: false,
                                font: { size: 10 },
                                callback: function (value) {
                                    var label = String(this.getLabelForValue(value));
                                    return label.length > 28 ? label.slice(0, 25) + '…' : label;
                                }
                            }
                        }
                    }
                }
            });
        }
        showEmpty(emptyWrapId, !all.length);
        if (!btn) return;
        btn.style.display = all.length ? 'inline-flex' : 'none';
        btn.textContent = 'Ampliar vista';
        btn.onclick = function () { openRankPop(modalTitle, modalSub, all, color); };
    }

    var pop = document.getElementById('rank-pop');
    function openRankPop(title, sub, items, color) {
        document.getElementById('rank-pop-title').textContent = title;
        document.getElementById('rank-pop-sub').textContent = sub + ' ' + items.length + ' en total.';
        document.getElementById('rank-pop-list').innerHTML = rankItemsHtml(items, color);
        pop.classList.add('open');
    }
    function closeRankPop() { pop.classList.remove('open'); }
    document.getElementById('rank-pop-close').addEventListener('click', closeRankPop);
    pop.addEventListener('click', function (e) { if (e.target === pop) closeRankPop(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeRankPop(); });

    function ownerColor(index) {
        var palette = ['#006837', '#2563eb', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#be185d', '#4d7c0f'];
        return palette[index % palette.length];
    }

    function ownerTimeSeries(owners, key, periods) {
        return owners.map(function (owner, index) {
            var totals = {};
            (owner[key] || []).forEach(function (point) { totals[point.period] = point.total; });
            return {
                label: owner.name,
                data: periods.map(function (period) { return totals[period] || 0; }),
                borderColor: ownerColor(index),
                backgroundColor: ownerColor(index),
                pointBackgroundColor: '#fff',
                pointBorderColor: ownerColor(index),
                pointBorderWidth: 2,
                tension: .35,
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 5,
                fill: false
            };
        });
    }

    function renderOwnerStatusChart(owners) {
        if (charts.s) charts.s.destroy();
        var labels = ['Pendiente', 'En proceso', 'Sancionado', 'Archivado'];
        var datasets = owners.map(function (owner, index) {
            var states = owner.states || {};
            return {
                label: owner.name,
                data: [
                    states['Pendiente'] || 0,
                    states['En proceso'] || states['En Proceso'] || 0,
                    states['Sancionado'] || 0,
                    states['Archivado'] || 0
                ],
                backgroundColor: ownerColor(index),
                borderRadius: 4,
                maxBarThickness: 26
            };
        });
        charts.s = new Chart(document.getElementById('status-chart'), {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 10 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } }
                }
            }
        });
        showEmpty('status-wrap', !owners.some(function (owner) {
            return Object.keys(owner.states || {}).some(function (state) { return owner.states[state] > 0; });
        }));
    }

    function renderReport(data) {
        var hasOwnerBreakdown = Array.isArray(data.by_user);
        var owners = data.by_user || [];
        var states = data.states || {};
        var finals = Object.keys(states).filter(function(s){ return ['Sancionado','Archivado'].indexOf(s)>-1; });

        document.getElementById('card-total').textContent      = data.total||0;
        document.getElementById('card-pendientes').textContent = (data.kpis && data.kpis.pendientes) || states.Pendiente || 0;
        document.getElementById('card-proceso').textContent    = (data.kpis && data.kpis.en_proceso) || states['En proceso'] || states['En Proceso'] || 0;
        document.getElementById('card-finalizados').textContent= finals.reduce(function(s,k){return s+(states[k]||0);},0);
        var anexos = data.anexos || {};
        document.getElementById('card-anexos').textContent = anexos.total||0;
        document.getElementById('card-anexos-previo').textContent = anexos.archivo_previo||0;
        document.getElementById('card-anexos-firmado').textContent = anexos.firmado||0;

        var dLab = (data.daily||[]).map(function(d){
            return new Intl.DateTimeFormat('es-CO',{day:'2-digit',month:'short'}).format(new Date(d.period+'T00:00:00'));
        });
        var dVals = (data.daily||[]).map(function(d){ return d.total; });
        renderTrendChart('daily-chart', 'daily-wrap', dLab, dVals, '#0891b2', 'Casos por día',
            hasOwnerBreakdown ? ownerTimeSeries(owners, 'daily', (data.daily||[]).map(function(d){ return d.period; })) : null);

        var wLab = (data.weekly||[]).map(function(w){
            var parts = String(w.period).match(/^(\d{4})-W?(\d{1,2})$/);
            return parts ? 'Sem ' + parseInt(parts[2], 10) + ' · ' + parts[1] : String(w.period);
        });
        var wVals = (data.weekly||[]).map(function(w){ return w.total; });
        renderTrendChart('weekly-chart', 'weekly-wrap', wLab, wVals, '#2563eb', 'Casos por semana',
            hasOwnerBreakdown ? ownerTimeSeries(owners, 'weekly', (data.weekly||[]).map(function(w){ return w.period; })) : null);

        var mLab = (data.monthly||[]).map(function(m){
            return new Intl.DateTimeFormat('es-CO',{month:'short',year:'2-digit'}).format(new Date(m.period+'-01T00:00:00'));
        });
        var mVals = (data.monthly||[]).map(function(m){
            return typeof m.total === 'number' ? m.total : Object.keys(m.states||{}).reduce(function(s,k){return s+(m.states[k]||0);},0);
        });
        renderTrendChart('monthly-chart', 'monthly-wrap', mLab, mVals, colors[0], 'Casos por mes',
            hasOwnerBreakdown ? ownerTimeSeries(owners, 'monthly', (data.monthly||[]).map(function(m){ return m.period; })) : null);

        renderTopList('cargo-list', 'cargo-wrap', 'cargo-more', data.by_modalidad || [], '#2563eb',
            'Modalidad y cargo', 'Modalidad y cargo de los procesos del período.');
        renderTopList('fault-list', 'fault-wrap', 'fault-more', data.pending_faults || [], '#f59e0b',
            'Tipo de falta', 'Todas las faltas registradas en el período.');

        if (hasOwnerBreakdown) {
            renderOwnerStatusChart(owners);
            return;
        }

        /* doughnut Estados globales */
        if (charts.s) charts.s.destroy();
        var st = data.states || {};
        var stSlices = [
            { label: 'Pendiente', value: st['Pendiente']||0, color: '#f59e0b' },
            { label: 'En proceso', value: st['En Proceso']||st['En proceso']||0, color: '#2563eb' },
            { label: 'Sancionado', value: st['Sancionado']||0, color: '#dc2626' },
            { label: 'Archivado', value: st['Archivado']||0, color: '#64748b' }
        ].filter(function (s) { return s.value > 0; });
        
        if (stSlices.length) {
            charts.s = new Chart(document.getElementById('status-chart'), {
                type:'doughnut',
                data:{ labels:stSlices.map(function(s){return s.label;}), datasets:[{ data:stSlices.map(function(s){return s.value;}), backgroundColor:stSlices.map(function(s){return s.color;}), borderWidth:1 }] },
                options:{ responsive:true, maintainAspectRatio:false, cutout:'60%',
                    plugins:{ legend:{position:'bottom',labels:{boxWidth:10,padding:16,font:{size:10}}},
                        tooltip:{callbacks:{label:function(ctx){
                            var t=ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                            return ctx.label+': '+ctx.raw+' ('+(t?((ctx.raw/t)*100).toFixed(1):0)+'%)';
                        }}} } }
            });
        }
        showEmpty('status-wrap', !stSlices.length);
    }

    function renderTrendChart(canvasId, wrapId, labels, values, color, datasetLabel, ownerDatasets) {
        if (charts[canvasId]) charts[canvasId].destroy();
        if (labels.length) {
            var datasets = ownerDatasets || [{
                label: datasetLabel,
                data: values,
                borderColor: color,
                backgroundColor: color === colors[0] ? 'rgba(22,163,74,.12)' : 'rgba(37,99,235,.12)',
                fill: true,
                tension: .35,
                borderWidth: 3,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderColor: color,
                pointBorderWidth: 2
            }];
            charts[canvasId] = new Chart(document.getElementById(canvasId), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: {
                            display: Array.isArray(ownerDatasets),
                            position: 'bottom',
                            labels: { boxWidth: 10, padding: 10, font: { size: 10 } }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { autoSkip: true, maxTicksLimit: 9, maxRotation: 0, font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148,163,184,.18)' },
                            ticks: { precision: 0, font: { size: 10 }, padding: 8 }
                        }
                    }
                }
            });
        }
        showEmpty(wrapId, !labels.length);
    }

    function loadStats() {
        syncForms();
        fetch(endpoint+'?'+new URLSearchParams(window.location.search).toString(),{headers:{'Accept':'application/json'}})
            .then(function(r){return r.json().then(function(d){if(!r.ok)throw new Error(d.message||'Error.');return d;});})
            .then(renderReport)
            .catch(function(e){errBox.textContent=e.message;errBox.style.display='block';});
    }

    loadStats();

    var form = document.getElementById('cases-form');
    var all  = document.getElementById('select-all');
    var chks = document.querySelectorAll('.case-check');
    all.addEventListener('change', function(){ chks.forEach(function(c){c.checked=all.checked;}); });
    form.querySelectorAll('[data-format]').forEach(function(btn){
        btn.addEventListener('click',function(e){
            if(!document.querySelectorAll('.case-check:checked').length){
                e.preventDefault();
                if (window.SIPD) {
                    SIPD.toast({ icon: 'warning', title: 'Selecciona casos', text: 'Marca al menos un caso para exportar.' });
                }
                return;
            }
            form.action=@json(url('/abogado/reportes/casos'))+'/'+btn.dataset.format;
        });
    });
});
</script>
@endsection
