@extends('layouts.master')

@section('styles')
<style>
    /* ── HEADER ──────────────────────────────────── */
    .rp-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
    .rp-head h1 { margin:0; font-size:20px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .rp-head p  { margin:2px 0 0; color:#64748b; font-size:12px; }

    /* ── BUTTONS ─────────────────────────────────── */
    .rbtn { border:0; border-radius:7px; padding:7px 12px; font-weight:700; font-size:11px; cursor:pointer; text-decoration:none; display:inline-flex; gap:5px; align-items:center; transition:opacity .15s; }
    .rbtn:hover { opacity:.82; }
    .rbtn.g   { background:var(--cth-green-text); color:#fff; }
    .rbtn.sl  { background:#f1f5f9; color:#334155; }
    .rbtn.pdf { background:#fee2e2; color:#b91c1c; }
    .rbtn.doc { background:#dbeafe; color:#1d4ed8; }
    .rbtn.xls { background:#dcfce7; color:var(--cth-green-text); }

    /* ── FILTERS ─────────────────────────────────── */
    .rp-filters { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:14px; display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end; }
    .rf { min-width:140px; flex:1; }
    .rf label { display:block; color:#64748b; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px; }
    .rf input  { width:100%; height:32px; border:1px solid #cbd5e1; border-radius:7px; padding:0 9px; font-size:12px; }
    .rp-error  { display:none; background:#fef2f2; color:#b91c1c; border-radius:8px; padding:8px 12px; margin-bottom:12px; font-size:12px; }

    /* ── KPI CARDS ───────────────────────────────── */
    .kpi-row   { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:14px; }
    .kpi-card  { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; }
    .kpi-card small  { color:#64748b; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; display:block; }
    .kpi-card strong { font-size:22px; font-weight:800; color:#0f172a; display:block; margin-top:2px; line-height:1; }

    /* ── CHARTS ──────────────────────────────────── */
    .charts-wrap { display:grid; grid-template-columns:2fr 1fr 1fr; gap:10px; margin-bottom:16px; }
    .ch-card     { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; }
    .ch-card h3  { margin:0 0 1px; font-size:12px; font-weight:700; color:#0f172a; }
    .ch-card p   { color:#64748b; font-size:10px; margin:0 0 8px; }
    .ch-wrap     { position:relative; height:160px; }
    .ch-empty    { display:none; color:#94a3b8; text-align:center; padding:40px 8px; font-size:12px; }

    /* ── DIVIDER ─────────────────────────────────── */
    .sec-div { display:flex; align-items:center; gap:10px; margin:16px 0 12px; }
    .sec-div h2   { margin:0; font-size:13px; font-weight:800; color:#0f172a; white-space:nowrap; }
    .sec-div span { color:#94a3b8; font-size:11px; white-space:nowrap; }
    .sec-div hr   { flex:1; border:none; border-top:1px solid #e2e8f0; }

    /* ── TABLE TOOLBAR ───────────────────────────── */
    .tbl-toolbar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
    .tbl-toolbar label { font-size:12px; color:#475569; font-weight:600; display:flex; align-items:center; gap:6px; cursor:pointer; }
    .btn-grp { display:flex; gap:5px; flex-wrap:wrap; }

    /* ── TABLE ───────────────────────────────────── */
    .case-tbl { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:auto; }
    .case-tbl table { width:100%; min-width:720px; border-collapse:collapse; font-size:11.5px; }
    .case-tbl thead tr { background:#f8fafc; }
    .case-tbl th { padding:6px 9px; border-bottom:1px solid #e2e8f0; text-align:left; color:#64748b; font-size:9.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
    .case-tbl td { padding:5px 9px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
    .case-tbl tr:last-child td { border-bottom:none; }
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

    @media (max-width:900px) {
        .rp-head { flex-direction:column; align-items:flex-start; }
        .kpi-row { grid-template-columns:repeat(2,1fr); }
        .charts-wrap { grid-template-columns:1fr; }
    }
    @media (max-width:560px) {
        .kpi-row { grid-template-columns:1fr 1fr; }
        .rf { width:100%; min-width:100%; }
    }
</style>
@endsection

@section('content')

{{-- HEADER --}}
<div class="rp-head">
    <div>
        <h1><i class="fas fa-chart-bar" style="color:var(--cth-green-text);"></i> Estadísticas y Reportes</h1>
        <p>Panorama global de los procesos disciplinarios activos, archivados y finalizados.</p>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
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
</div>

{{-- FILTROS --}}
<form class="rp-filters" method="GET" action="{{ route('abogado.reportes') }}" id="report-filters">
    <div class="rf"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}"></div>
    <div class="rf"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}"></div>
    <div class="rf"><label for="q">Buscar</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Conductor, cédula, placa…"></div>
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

{{-- CHARTS (3 en una fila) --}}
<div class="charts-wrap">
    <div class="ch-card">
        <h3>Casos por mes</h3><p>Distribución mensual por estado.</p>
        <div class="ch-wrap" id="monthly-wrap">
            <canvas id="monthly-chart"></canvas>
            <div class="ch-empty">Sin datos para el período.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Por estado</h3><p>Proporción del período.</p>
        <div class="ch-wrap" id="state-wrap">
            <canvas id="state-chart"></canvas>
            <div class="ch-empty">Sin estados.</div>
        </div>
    </div>
    <div class="ch-card">
        <h3>Faltas activas</h3><p>Tipos con más procesos abiertos.</p>
        <div class="ch-wrap" id="fault-wrap">
            <canvas id="fault-chart"></canvas>
            <div class="ch-empty">Sin faltas.</div>
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
        <div class="btn-grp">
            <button class="rbtn pdf" data-format="pdf"   type="submit"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="rbtn doc" data-format="word"  type="submit"><i class="fas fa-file-word"></i> Word</button>
            <button class="rbtn xls" data-format="excel" type="submit"><i class="fas fa-file-excel"></i> Excel</button>
        </div>
    </div>

    <div class="case-tbl">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Proceso</th>
                    <th>Conductor</th>
                    <th>Cédula</th>
                    <th>Placa</th>
                    <th>Tipo de Falta</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Modalidad</th>
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
                    <td>{{ $caso->placa    ?: '—' }}</td>
                    <td>{{ $caso->tipo_falta ?: '—' }}</td>
                    <td><span class="sb {{ strtolower(str_replace(' ', '-', $caso->estado)) }}">{{ $caso->estado }}</span></td>
                    <td>{{ $caso->created_at ? $caso->created_at->format('d/m/Y') : '—' }}</td>
                    <td>{{ $caso->modalidad ?: '—' }}</td>
                    <td style="text-align:right;"><a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="act-lnk"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
            @empty
                <tr><td class="empty-c" colspan="10"><i class="fas fa-search" style="display:block;font-size:18px;margin-bottom:6px;opacity:.35;"></i>No hay casos que coincidan.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($casos->hasPages())
            <div class="tbl-pager">
                <span>{{ $casos->firstItem() }}–{{ $casos->lastItem() }} de {{ $casos->total() }}</span>
                <span>{{ $casos->links() }}</span>
            </div>
        @endif
    </div>
</form>

{{-- ═══════════════════════════════════════════════ --}}
{{-- HISTORIAL DE CASOS CERRADOS                     --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="sec-div" style="margin-top:24px;">
    <h2><i class="fas fa-archive" style="color:var(--cth-green-text);"></i> Historial — Casos Cerrados</h2>
    <span>{{ $hStats['total'] }} registro{{ $hStats['total'] === 1 ? '' : 's' }}</span>
    <hr>
</div>

{{-- Mini KPIs del historial --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;">
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
</div>

{{-- Tabla historial --}}
<div class="case-tbl">
    <table>
        <thead>
            <tr>
                <th>Proceso</th>
                <th>Conductor</th>
                <th>Cédula</th>
                <th>Placa</th>
                <th>Tipo de Falta</th>
                <th>Resultado</th>
                <th>Abogado</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th>Duración</th>
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
                <td>{{ $h->placa    ?: '—' }}</td>
                <td>{{ $h->tipo_falta ?: '—' }}</td>
                <td>
                    <span class="sb {{ strtolower(str_replace(' ', '-', $h->estado)) }}">{{ $h->estado }}</span>
                </td>
                <td style="color:#475569;">{{ $h->abogado }}</td>
                <td style="white-space:nowrap;">{{ $h->fecha_inicio }}</td>
                <td style="white-space:nowrap;">{{ $h->fecha_cierre }}</td>
                <td style="text-align:center;">
                    @if($h->duracion_dias !== null)
                        <span style="font-weight:700;color:{{ $h->duracion_dias > 30 ? '#b91c1c' : 'var(--cth-green-text)' }};">
                            {{ $h->duracion_dias }}d
                        </span>
                    @else
                        —
                    @endif
                </td>
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
                <td class="empty-c" colspan="12">
                    <i class="fas fa-archive" style="display:block;font-size:18px;margin-bottom:6px;opacity:.35;"></i>
                    No hay casos archivados ni sancionados aún.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
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
        w.querySelector('canvas').style.display    = empty ? 'none'  : 'block';
        w.querySelector('.ch-empty').style.display = empty ? 'block' : 'none';
    }

    function renderReport(data) {
        var states = Object.keys(data.states || {});
        var values = states.map(function(s){ return data.states[s]; });
        var finals = states.filter(function(s){ return ['Sancionado','Archivado'].indexOf(s)>-1; });

        document.getElementById('card-total').textContent      = data.total||0;
        document.getElementById('card-pendientes').textContent = data.states.Pendiente||0;
        document.getElementById('card-proceso').textContent    = data.states['En Proceso']||0;
        document.getElementById('card-finalizados').textContent= finals.reduce(function(s,k){return s+data.states[k];},0);

        /* bar mensual */
        if (charts.m) charts.m.destroy();
        var mLab = (data.monthly||[]).map(function(m){
            return new Intl.DateTimeFormat('es-CO',{month:'short',year:'2-digit'}).format(new Date(m.period+'-01T00:00:00'));
        });
        charts.m = new Chart(document.getElementById('monthly-chart'),{
            type:'bar',
            data:{ labels:mLab, datasets: states.map(function(s,i){
                return { label:s, data:(data.monthly||[]).map(function(m){return m.states[s]||0;}), backgroundColor:colors[i%colors.length], borderRadius:3 };
            })},
            options:{ responsive:true, maintainAspectRatio:false,
                interaction:{mode:'index',intersect:false},
                plugins:{legend:{labels:{boxWidth:10,font:{size:9}}}},
                scales:{x:{ticks:{font:{size:9}}}, y:{beginAtZero:true, ticks:{precision:0,font:{size:9}}}} }
        });
        showEmpty('monthly-wrap', !mLab.length||!states.length);

        /* doughnut */
        if (charts.s) charts.s.destroy();
        charts.s = new Chart(document.getElementById('state-chart'),{
            type:'doughnut',
            data:{ labels:states, datasets:[{ data:values, backgroundColor:states.map(function(_,i){return colors[i%colors.length];}), borderWidth:1 }] },
            options:{ responsive:true, maintainAspectRatio:false, cutout:'60%',
                plugins:{ legend:{position:'bottom',labels:{boxWidth:9,font:{size:9}}},
                    tooltip:{callbacks:{label:function(ctx){
                        var t=ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                        return ctx.label+': '+ctx.raw+' ('+(t?((ctx.raw/t)*100).toFixed(1):0)+'%)';
                    }}} } }
        });
        showEmpty('state-wrap', !states.length);

        /* bar faltas */
        if (charts.f) charts.f.destroy();
        var faults = data.pending_faults||[];
        charts.f = new Chart(document.getElementById('fault-chart'),{
            type:'bar',
            data:{ labels:faults.map(function(f){return f.label;}), datasets:[{ label:'Pendientes', data:faults.map(function(f){return f.total;}), backgroundColor:'#f59e0b', borderRadius:3 }] },
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
                plugins:{legend:{display:false}},
                scales:{x:{beginAtZero:true,ticks:{precision:0,font:{size:9}}}, y:{ticks:{font:{size:9}}}} }
        });
        showEmpty('fault-wrap', !faults.length);
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
            if(!document.querySelectorAll('.case-check:checked').length){e.preventDefault();alert('Selecciona al menos un caso.');return;}
            form.action=@json(url('/abogado/reportes/casos'))+'/'+btn.dataset.format;
        });
    });
});
</script>
@endsection
