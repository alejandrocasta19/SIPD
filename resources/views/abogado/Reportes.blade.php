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
    .charts-wrap { display:grid; grid-template-columns:minmax(0,1.5fr) repeat(3, minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .ch-card     { background:#fff; border: 1px solid var(--cth-border); border-radius:10px; padding:12px 14px; min-width:0; display:flex; flex-direction:column; }
    .ch-card h3  { margin:0 0 1px; font-size:12px; font-weight:700; color:#0f172a; }
    .ch-card p   { color:#64748b; font-size:10px; margin:0 0 8px; }
    .ch-wrap     { position:relative; height:160px; flex:0 0 160px; }
    .ch-wrap.ch-rank { display:flex; flex-direction:column; overflow:hidden; }
    .ch-empty    { display:none; color:#94a3b8; text-align:center; padding:40px 8px; font-size:12px; }
    .top-more    { border:1px solid #dbeafe; background:#f8fafc; color:#1d4ed8; border-radius:8px; padding:6px 10px; font-size:11px; font-weight:700; cursor:pointer; display:none; width:100%; margin-top:auto; justify-content:center; flex-shrink:0; }
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
    .case-tbl th { padding:6px 9px; border:1px solid var(--cth-border); text-align:left; color:#64748b; font-size:9.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
    .case-tbl td { padding:5px 9px; border:1px solid var(--cth-border); color:#334155; vertical-align:middle; }
    .case-tbl tr:hover td { background:#f8fafc; }
    .proc-lnk { color:var(--cth-green-text); font-weight:700; text-decoration:none; font-size:10.5px; font-family:monospace; }
    .case-tbl td b { font-weight:600; color:#0f172a; }
    .act-lnk  { color:#2563eb; text-decoration:none; font-size:11px; font-weight:600; display:inline-flex; align-items:center; gap:3px; }
    .act-lnk:hover { text-decoration:underline; }
    .empty-c  { text-align:center; padding:28px; color:#94a3b8; font-size:12px; }
    .tbl-pager{ display:flex; justify-content:space-between; align-items:center; padding:8px 10px; color:#64748b; font-size:11px; border-top:1px solid #f1f5f9; }

    /* ── BADGES ──────────────────────────────────── */
    .sb { padding:2px 7px; border-radius:999px; font-size:9.5px; font-weight:700; border:1px solid transparent; letter-spacing:.03em; white-space:nowrap; display:inline-block; }
    .sb.pendiente  { background:#fef9c3; color:#a16207; border-color:#fef08a; }
    .sb.en-proceso { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .sb.sancionado { background:#fef2f2; color:#b91c1c; border-color:#fca5a5; }
    .sb.archivado  { background:#f1f5f9; color:#334155; border-color:#e2e8f0; }

    @media (max-width:1200px) {
        .charts-wrap { grid-template-columns:1fr 1fr; }
        .charts-wrap .ch-card:first-child { grid-column: 1 / -1; }
        .hist-kpis { grid-template-columns:repeat(3, minmax(0,1fr)); }
    }
    @media (max-width:900px) {
        .kpi-row { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .charts-wrap { grid-template-columns:1fr; }
        .charts-wrap .ch-card:first-child { grid-column: auto; }
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
        <h3>Casos por mes</h3><p>Volumen registrado en el período.</p>
        <div class="ch-wrap" id="monthly-wrap">
            <canvas id="monthly-chart"></canvas>
            <div class="ch-empty">Sin datos para el período.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Modalidad y cargo</h3>
        <p>Top 3 modalidades con más procesos.</p>
        <div class="ch-wrap ch-rank" id="cargo-wrap">
            <ol class="top-list" id="cargo-list"></ol>
            <button type="button" class="top-more" id="cargo-more">Ampliar vista</button>
            <div class="ch-empty">Sin cargos registrados.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Tipo de falta</h3>
        <p>Top 3 faltas con más casos.</p>
        <div class="ch-wrap ch-rank" id="fault-wrap">
            <ol class="top-list" id="fault-list"></ol>
            <button type="button" class="top-more" id="fault-more">Ampliar vista</button>
            <div class="ch-empty">Sin faltas.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Reincidencias</h3><p>Trabajadores con un caso o con varios.</p>
        <div class="ch-wrap" id="reinc-wrap">
            <canvas id="reinc-chart"></canvas>
            <div class="ch-empty">Sin cédulas para comparar.</div>
        </div>
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
                    <th style="text-align:right;">Acción</th>
                </tr>
            </thead>
            <tbody>
            @forelse($casos as $caso)
                <tr>
                    <td style="width:26px;"><input type="checkbox" name="ids[]" value="{{ $caso->id }}" class="case-check"></td>
                    <td><a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="proc-lnk">PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</a></td>
                    <td><b>{{ $caso->nombre }}</b></td>
                    <td>{{ $caso->cedula   ?: '—' }}</td>
                    <td>{{ \App\Support\Modalidades::textoPlaca($caso->modalidad, $caso->placa) }}</td>
                    <td>{{ $caso->tipo_falta ?: '—' }}</td>
                    <td><span class="sb {{ strtolower(str_replace(' ', '-', $caso->estadoVisible())) }}">{{ $caso->estadoVisible() }}</span></td>
                    <td>{{ $caso->created_at ? $caso->created_at->format('d/m/Y') : '—' }}</td>
                    <td>{{ \App\Support\Modalidades::etiquetaCaso($caso->modalidad, $caso->cargo) }}</td>
                    <td style="text-align:center;">{{ $caso->anexos_count ?: '—' }}</td>
                    <td style="text-align:right;"><a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="act-lnk"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
            @empty
                <tr><td class="empty-c" colspan="11"><i class="fas fa-search" style="display:block;font-size:18px;margin-bottom:6px;opacity:.35;"></i>No hay casos que coincidan.</td></tr>
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
                <th style="text-align:right;">Ver</th>
            </tr>
        </thead>
        <tbody>
        @forelse($historial as $h)
            <tr>
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
                    <a href="{{ route('abogado.detalleproceso', $h->id) }}" class="act-lnk">
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
        var list = document.getElementById(listId);
        var btn = document.getElementById(btnId);
        var all = items || [];
        list.innerHTML = rankItemsHtml(all.slice(0, 3), color);
        list.style.display = all.length ? 'block' : 'none';
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

    function renderReport(data) {
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

        /* línea mensual: solo volumen, el estado ya está en las tarjetas */
        if (charts.m) charts.m.destroy();
        var mLab = (data.monthly||[]).map(function(m){
            return new Intl.DateTimeFormat('es-CO',{month:'short',year:'2-digit'}).format(new Date(m.period+'-01T00:00:00'));
        });
        var mVals = (data.monthly||[]).map(function(m){
            return typeof m.total === 'number' ? m.total : Object.keys(m.states||{}).reduce(function(s,k){return s+(m.states[k]||0);},0);
        });
        charts.m = new Chart(document.getElementById('monthly-chart'),{
            type:'line',
            data:{ labels:mLab, datasets:[{
                label:'Casos', data:mVals,
                borderColor:colors[0], backgroundColor:'rgba(22,163,74,.12)',
                fill:true, tension:.3, pointRadius:3, pointBackgroundColor:colors[0]
            }]},
            options:{ responsive:true, maintainAspectRatio:false,
                plugins:{legend:{display:false}},
                scales:{x:{ticks:{font:{size:9}}}, y:{beginAtZero:true, ticks:{precision:0,font:{size:9}}}} }
        });
        showEmpty('monthly-wrap', !mLab.length);

        renderTopList(
            'cargo-list', 'cargo-wrap', 'cargo-more',
            data.by_modalidad || [],
            '#2563eb',
            'Modalidad y cargo',
            'Modalidad y cargo de los procesos del período.'
        );
        renderTopList(
            'fault-list', 'fault-wrap', 'fault-more',
            data.pending_faults || [],
            '#f59e0b',
            'Tipo de falta',
            'Todas las faltas registradas en el período.'
        );

        /* doughnut reincidencias */
        if (charts.r) charts.r.destroy();
        var reinc = data.reincidencias || {};
        var reincSlices = [
            { label: 'Primera vez', value: reinc.primera_vez||0, color: colors[0] },
            { label: 'Reincidentes', value: reinc.reincidentes||0, color: '#dc2626' }
        ].filter(function (s) { return s.value > 0; });
        if (reincSlices.length) {
            charts.r = new Chart(document.getElementById('reinc-chart'),{
                type:'doughnut',
                data:{ labels:reincSlices.map(function(s){return s.label;}), datasets:[{ data:reincSlices.map(function(s){return s.value;}), backgroundColor:reincSlices.map(function(s){return s.color;}), borderWidth:1 }] },
                options:{ responsive:true, maintainAspectRatio:false, cutout:'60%',
                    plugins:{ legend:{position:'bottom',labels:{boxWidth:9,font:{size:9}}},
                        tooltip:{callbacks:{label:function(ctx){
                            var t=ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                            return ctx.label+': '+ctx.raw+' ('+(t?((ctx.raw/t)*100).toFixed(1):0)+'%)';
                        }}} } }
            });
        }
        showEmpty('reinc-wrap', !reincSlices.length);
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
