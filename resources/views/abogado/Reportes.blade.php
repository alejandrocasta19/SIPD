@extends('layouts.master')

@section('styles')
<style>
    /* Reportes and Casos head combined styles */
    .report-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px; }
    .report-head h1 { margin:0 0 5px; font-size:28px; }
    .report-head p { margin:0; color:#64748b; }
    .report-filters { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:18px; display:flex; flex-wrap:wrap; gap:10px; align-items:end;}
    .report-field { min-width:170px; flex: 1; }
    .report-field label { display:block; color:#64748b; font-size:12px; font-weight:700; margin-bottom:5px; }
    .report-field input { width:100%; height:38px; border:1px solid #cbd5e1; border-radius:8px; padding:0 10px; }
    .report-btn { border:0; border-radius:8px; padding:10px 13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; gap:7px; align-items:center; }
    .report-btn.primary { background:#166534; color:#fff; }
    .report-btn.light { background:#f1f5f9; color:#334155; }
    .report-btn.pdf { background:#fee2e2; color:#b91c1c; }
    .report-btn.word { background:#dbeafe; color:#1d4ed8; }
    .report-btn.excel { background:#dcfce7; color:#166534; }
    .report-error { display:none; background:#fef2f2; color:#b91c1c; border-radius:8px; padding:10px 12px; margin-bottom:14px; }
    .report-cards { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:18px; }
    .report-card, .chart-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .report-card { padding:16px; }
    .report-card small { color:#64748b; display:block; }
    .report-card strong { font-size:28px; color:#0f172a; display:block; margin-top:5px; }
    .charts-grid { display:grid; grid-template-columns:1.4fr 1fr; gap:16px; margin-bottom: 24px;}
    .chart-card { padding:16px; margin-bottom:0; }
    .chart-card.full { grid-column:1 / -1; }
    .chart-card h3 { margin:0 0 3px; font-size:16px; }
    .chart-card p { color:#64748b; font-size:12px; margin:0 0 12px; }
    .chart-wrap { position:relative; height:300px; }
    .chart-empty { display:none; color:#94a3b8; text-align:center; padding:80px 10px; }
    @media (max-width: 900px) { .report-head { flex-direction:column; } .report-cards { grid-template-columns:repeat(2, 1fr); } .charts-grid { grid-template-columns:1fr; } .chart-card.full { grid-column:auto; } }
    @media (max-width: 560px) { .report-cards { grid-template-columns:1fr; } .report-field { width:100%; min-width: 100%; } }
    
    /* Cases table styles */
    .cases-section-head { margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }
    .cases-section-head h2 { margin: 0; font-size: 20px;}
    .cases-section-head p { margin: 0; color: #64748b; font-size: 13px; }
    .case-table { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:auto; }
    .case-table table { width:100%; min-width:920px; border-collapse:collapse; font-size:13px; }
    .case-table th, .case-table td { padding:12px; border-bottom:1px solid #f1f5f9; text-align:left; }
    .case-table th { color:#64748b; font-size:11px; letter-spacing:.05em; }
    .case-table a { color:#166534; font-weight:700; text-decoration:none; }
    .empty-cases { text-align:center; padding:42px; color:#94a3b8; }
    .cases-pager { display:flex; justify-content:space-between; padding:14px; color:#64748b; }
</style>
@endsection

@section('content')
<div class="report-head">
    <div>
        <h1>Estadísticas y reportes</h1>
        <p>Analiza el comportamiento global y detalla los casos específicos del proceso.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <form method="POST" action="{{ route('abogado.reportes.global', 'pdf') }}" class="export-form">@csrf<input type="hidden" name="desde"><input type="hidden" name="hasta"><input type="hidden" name="q"><button class="report-btn pdf" type="submit"><i class="fas fa-file-pdf"></i> PDF Global</button></form>
        <form method="POST" action="{{ route('abogado.reportes.global', 'word') }}" class="export-form">@csrf<input type="hidden" name="desde"><input type="hidden" name="hasta"><input type="hidden" name="q"><button class="report-btn word" type="submit"><i class="fas fa-file-word"></i> Word Global</button></form>
        <form method="POST" action="{{ route('abogado.reportes.global', 'excel') }}" class="export-form">@csrf<input type="hidden" name="desde"><input type="hidden" name="hasta"><input type="hidden" name="q"><button class="report-btn excel" type="submit"><i class="fas fa-file-excel"></i> Excel Global</button></form>
    </div>
</div>

<form class="report-filters" method="GET" action="{{ route('abogado.reportes') }}" id="report-filters">
    <div class="report-field"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}"></div>
    <div class="report-field"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}"></div>
    <div class="report-field"><label for="q">Buscar caso</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar conductor, cédula, placa..."></div>
    <button class="report-btn primary" type="submit"><i class="fas fa-search"></i> Filtrar</button>
    <a href="{{ route('abogado.reportes') }}" class="report-btn light"><i class="fas fa-undo"></i> Limpiar</a>
</form>

<div class="report-error" id="report-error"></div>
<div class="report-cards">
    <div class="report-card"><small>Total de casos</small><strong id="card-total">0</strong></div>
    <div class="report-card"><small>Pendientes</small><strong id="card-pendientes">0</strong></div>
    <div class="report-card"><small>En proceso</small><strong id="card-proceso">0</strong></div>
    <div class="report-card"><small>Estados finalizados</small><strong id="card-finalizados">0</strong></div>
</div>

<div class="charts-grid">
    <section class="chart-card full"><h3>Casos registrados por mes</h3><p>Distribución mensual por los estados reales de los procesos.</p><div class="chart-wrap" id="monthly-wrap"><canvas id="monthly-chart"></canvas><div class="chart-empty">No hay datos para el período seleccionado.</div></div></section>
    <section class="chart-card"><h3>Distribución por estado</h3><p>Cantidad y porcentaje de casos del período.</p><div class="chart-wrap" id="state-wrap"><canvas id="state-chart"></canvas><div class="chart-empty">No hay estados para mostrar.</div></div></section>
    <section class="chart-card"><h3>Tipos de faltas pendientes</h3><p>Los tipos con mayor cantidad de procesos pendientes.</p><div class="chart-wrap" id="fault-wrap"><canvas id="fault-chart"></canvas><div class="chart-empty">No hay faltas pendientes.</div></div></section>
</div>

<!-- Cases Section -->
<div class="cases-section-head">
    <div>
        <h2>Detalle de casos</h2>
        <p>Mostrando reportes detallados para la consulta actual. ({{ $casos->total() }} encontrado{{ $casos->total() === 1 ? '' : 's' }}).</p>
    </div>
</div>

<form id="cases-form" method="POST" action="{{ url('/abogado/reportes/casos/pdf') }}">
    @csrf
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
        <label style="font-size:13px;color:#475569;"><input type="checkbox" id="select-all"> Seleccionar todos</label>
        <button class="report-btn pdf" data-format="pdf" type="submit" style="padding: 6px 10px; font-size:12px;"><i class="fas fa-file-pdf"></i> Exportar selección (PDF)</button>
        <button class="report-btn word" data-format="word" type="submit" style="padding: 6px 10px; font-size:12px;"><i class="fas fa-file-word"></i> Exportar selección (Word)</button>
        <button class="report-btn excel" data-format="excel" type="submit" style="padding: 6px 10px; font-size:12px;"><i class="fas fa-file-excel"></i> Exportar selección (Excel)</button>
    </div>

    <div class="case-table">
        <table>
            <thead><tr><th><span class="sr-only">Seleccionar</span></th><th>PROCESO</th><th>CONDUCTOR</th><th>CÉDULA</th><th>PLACA</th><th>TIPO DE FALTA</th><th>ESTADO</th><th>FECHA</th><th>MODALIDAD</th></tr></thead>
            <tbody>
            @forelse($casos as $caso)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $caso->id }}" class="case-check"></td>
                    <td><a href="{{ route('abogado.detalleproceso', $caso->id) }}">PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</a></td>
                    <td>{{ $caso->nombre }}</td><td>{{ $caso->cedula ?: '—' }}</td><td>{{ $caso->placa ?: '—' }}</td>
                    <td>{{ $caso->tipo_falta ?: '—' }}</td><td>{{ $caso->estado }}</td>
                    <td>{{ $caso->created_at ? $caso->created_at->format('Y-m-d') : '—' }}</td><td>{{ $caso->modalidad ?: '—' }}</td>
                </tr>
            @empty
                <tr><td class="empty-cases" colspan="9">No hay casos que coincidan con la búsqueda.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($casos->hasPages())<div class="cases-pager"><span>{{ $casos->firstItem() }}-{{ $casos->lastItem() }} de {{ $casos->total() }}</span><span>{{ $casos->links() }}</span></div>@endif
    </div>
</form>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var endpoint = @json(route('abogado.reportes.datos'));
    var charts = {};
    var colors = ['#166534', '#2563eb', '#f59e0b', '#dc2626', '#64748b', '#7c3aed', '#0891b2'];
    var errorBox = document.getElementById('report-error');

    function syncForms() {
        var params = new URLSearchParams(window.location.search);
        var desde = params.get('desde') || '';
        var hasta = params.get('hasta') || '';
        var q = params.get('q') || '';
        document.querySelectorAll('.export-form').forEach(function (form) {
            form.querySelector('[name="desde"]').value = desde;
            form.querySelector('[name="hasta"]').value = hasta;
            form.querySelector('[name="q"]').value = q;
        });
    }

    function showEmpty(id, empty) {
        var wrap = document.getElementById(id);
        wrap.querySelector('canvas').style.display = empty ? 'none' : 'block';
        wrap.querySelector('.chart-empty').style.display = empty ? 'block' : 'none';
    }

    function renderReport(data) {
        var states = Object.keys(data.states || {});
        var values = states.map(function (state) { return data.states[state]; });
        var finalStates = states.filter(function (state) { return ['Sancionado', 'Archivado'].indexOf(state) !== -1; });
        document.getElementById('card-total').textContent = data.total || 0;
        document.getElementById('card-pendientes').textContent = data.states.Pendiente || 0;
        document.getElementById('card-proceso').textContent = data.states['En Proceso'] || 0;
        document.getElementById('card-finalizados').textContent = finalStates.reduce(function (sum, state) { return sum + data.states[state]; }, 0);

        if (charts.monthly) charts.monthly.destroy();
        var monthlyLabels = (data.monthly || []).map(function (month) { return new Intl.DateTimeFormat('es-CO', { month: 'long', year: 'numeric' }).format(new Date(month.period + '-01T00:00:00')); });
        charts.monthly = new Chart(document.getElementById('monthly-chart'), { type: 'bar', data: { labels: monthlyLabels, datasets: states.map(function (state, index) { return { label: state, data: (data.monthly || []).map(function (month) { return month.states[state] || 0; }), backgroundColor: colors[index % colors.length], borderRadius: 5 }; }) }, options: { responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, scales:{y:{beginAtZero:true, ticks:{precision:0}}} } });
        showEmpty('monthly-wrap', !monthlyLabels.length || !states.length);

        if (charts.state) charts.state.destroy();
        charts.state = new Chart(document.getElementById('state-chart'), { type:'doughnut', data:{ labels:states, datasets:[{ data:values, backgroundColor:states.map(function (_, index) { return colors[index % colors.length]; }) }] }, options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}, tooltip:{callbacks:{label:function (context) { var total = context.dataset.data.reduce(function (sum, value) { return sum + value; }, 0); var percent = total ? ((context.raw / total) * 100).toFixed(1) : 0; return context.label + ': ' + context.raw + ' (' + percent + '%)'; }}}} } });
        showEmpty('state-wrap', !states.length);

        if (charts.fault) charts.fault.destroy();
        var faults = data.pending_faults || [];
        charts.fault = new Chart(document.getElementById('fault-chart'), { type:'bar', data:{ labels:faults.map(function (item) { return item.label; }), datasets:[{ label:'Casos pendientes', data:faults.map(function (item) { return item.total; }), backgroundColor:'#f59e0b', borderRadius:5 }] }, options:{indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true, ticks:{precision:0}}}} });
        showEmpty('fault-wrap', !faults.length);
    }
    
    function loadStatsFromQuery() {
        syncForms();
        var params = new URLSearchParams(window.location.search);
        fetch(endpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json().then(function (data) { 
                if (!response.ok) {
                    var errorMsg = data.message || 'No fue posible cargar el reporte.';
                    if (data.errors) {
                        var firstKey = Object.keys(data.errors)[0];
                        if (data.errors[firstKey] && data.errors[firstKey][0]) {
                            errorMsg = data.errors[firstKey][0];
                        }
                    }
                    throw new Error(errorMsg); 
                } 
                return data; 
            }); })
            .then(renderReport)
            .catch(function (error) { errorBox.textContent = error.message; errorBox.style.display = 'block'; });
    }

    loadStatsFromQuery();

    // Cases interactions
    var form = document.getElementById('cases-form');
    var selectAll = document.getElementById('select-all');
    var checks = document.querySelectorAll('.case-check');
    selectAll.addEventListener('change', function () { checks.forEach(function (check) { check.checked = selectAll.checked; }); });
    form.querySelectorAll('[data-format]').forEach(function (button) { button.addEventListener('click', function (event) { var selected = document.querySelectorAll('.case-check:checked'); if (!selected.length) { event.preventDefault(); alert('Selecciona al menos un caso.'); return; } form.action = @json(url('/abogado/reportes/casos')) + '/' + button.dataset.format; }); });
});
</script>
@endsection
