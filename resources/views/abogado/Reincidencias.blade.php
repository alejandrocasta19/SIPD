@extends('layouts.master')

@php $pageTitle = 'Reincidencias'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Reincidencias</h1>
            <p>Historial consolidado de procesos por trabajador.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $workersGrouped->total() }} trabajadores</span>
            <span class="stat-chip yellow">{{ $workersGrouped->getCollection()->where('es_reincidente', true)->count() }} reincidentes</span>
            <a class="btn-add" href="{{ route('abogado.registro') }}"><i class="fas fa-plus"></i> Registrar proceso</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .table-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 24px; }
    .worker-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .worker-info { flex: 1; }
    .worker-info b { font-size: 16px; color: #0f172a; display: block; margin-bottom: 2px; }
    .worker-info span { font-size: 13px; color: #64748b; }
    
    .st-reincidente { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; border-radius: 999px; background: #fff1f2; color: #e11d48; margin-right: 12px; }
    .st-normal { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; border-radius: 999px; background: #f0fdf4; color: var(--cth-green-text); margin-right: 12px; }

    table.proc { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.proc th { text-align: left; padding: 10px 20px; color: #94a3b8; font-size: 11px; letter-spacing: .06em; font-weight: 700; border: 1px solid var(--cth-border); white-space: nowrap; text-transform: uppercase; }
    table.proc td { padding: 10px 20px; border: 1px solid var(--cth-border); color: #334155; vertical-align: middle; }
    table.proc tbody tr:hover { background: #fafbfc; }

    .id { color: var(--cth-green); font-weight: 700; text-decoration: none; }
    .name { font-weight: 600; color: #0f172a; }
    .st { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; }
    .st i { font-size: 8px; }
    
    .dot-pend { color: #f59e0b; }
    .dot-proc { color: #3b82f6; }
    .dot-sanc { color: #f43f5e; }
    .dot-arch { color: #94a3b8; }

    .acts { display: flex; gap: 8px; }
    .acts a { width: 30px; height: 30px; border: 0; background: #f1f5f9; color: #64748b; border-radius: 8px; display: grid; place-items: center; text-decoration: none; cursor: pointer; }
    .acts a:hover { background: #ecfdf5; color: var(--cth-green-text); }

    .empty { text-align: center; padding: 40px 16px; color: #94a3b8; background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    
    .pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; }
    .pager-pages { display: flex; gap: 6px; align-items: center; }
    .pager a, .pager span.current { min-width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; text-decoration: none; color: #64748b; background: #fff; border: 1px solid #e2e8f0; }
    .pager span.current { background: var(--cth-green-bright); border-color: var(--cth-green-bright); color: #fff; font-weight: 700; }
    
    .global-pager-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 24px; }
    .casos-pager {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-top: 1px solid #e2e8f0;
        color: #94a3b8;
        font-size: 12px;
    }
    .casos-pager-nav { display: flex; gap: 6px; }
    .casos-pager .sipd-page[disabled] { opacity: .45; cursor: default; }

    @media (max-width: 900px) { .toolbar { grid-template-columns: 1fr; } .proc-head { flex-direction: column; } .table-card { overflow-x: auto; } }
</style>
@endsection

@section('scripts')
<script>
(function () {
    document.querySelectorAll('.js-casos-card').forEach(function (card) {
        var rows = Array.prototype.slice.call(card.querySelectorAll('tbody tr'));
        var nav = card.querySelector('.js-casos-pager');
        var perPage = Math.max(1, parseInt(card.getAttribute('data-per-page'), 10) || 10);
        var page = 1;
        var total = rows.length;
        if (!nav || total === 0) return;

        function render() {
            var pages = Math.max(1, Math.ceil(total / perPage));
            if (page < 1) page = 1;
            if (page > pages) page = pages;
            var from = (page - 1) * perPage;
            var to = Math.min(from + perPage, total);
            rows.forEach(function (row, index) {
                row.hidden = index < from || index >= to;
            });
            var needsPager = total > perPage;
            nav.hidden = !needsPager;
            var count = nav.querySelector('.js-casos-count');
            if (count) {
                count.textContent = (from + 1) + '-' + to + ' de ' + total + ' casos';
            }
            var prev = nav.querySelector('.js-casos-prev');
            var next = nav.querySelector('.js-casos-next');
            if (prev) prev.disabled = page <= 1;
            if (next) next.disabled = page >= pages;
        }

        var prev = nav.querySelector('.js-casos-prev');
        var next = nav.querySelector('.js-casos-next');
        if (prev) prev.addEventListener('click', function () { page -= 1; render(); });
        if (next) next.addEventListener('click', function () { page += 1; render(); });
        render();
    });
})();
</script>
@endsection

@section('content')
    <form class="toolbar" method="GET" action="{{ route('abogado.reincidencias') }}">
        <input type="hidden" name="per_page" value="{{ $workersGrouped->perPage() }}">
        <div class="search">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nombre del conductor o cédula...">
        </div>
        @if(!empty($search))
            <a href="{{ route('abogado.reincidencias') }}" class="btn-clear">Limpiar</a>
        @else
            <button type="submit" style="display:none;"></button>
        @endif
    </form>

    @if($workersGrouped->isEmpty())
        <div class="empty">No se encontraron antecedentes o registros de reincidencia.</div>
    @else
        @foreach($workersGrouped as $worker)
            <div class="table-card">
                <div class="worker-header">
                    <div class="worker-info">
                        <b>{{ $worker->nombre }}</b>
                        <span>
                            @if($worker->cedula) C.C. {{ $worker->cedula }} · @endif
                            {{ $worker->modalidad ?: 'Sin modalidad' }}
                        </span>
                    </div>
                    <div>
                        @if($worker->es_reincidente)
                            <span class="st-reincidente">
                                <i class="fas fa-exclamation-triangle"></i> Reincidente ({{ $worker->total_casos }} Casos)
                            </span>
                        @else
                            <span class="st-normal">
                                <i class="fas fa-check-circle"></i> 1 Caso
                            </span>
                        @endif
                        <a href="{{ route('abogado.registro', ['nombre' => $worker->nombre, 'cedula' => $worker->cedula, 'modalidad' => $worker->modalidad, 'telefono' => $worker->telefono]) }}" class="btn-add" style="padding: 7px 12px; font-size: 12px;">
                            Nuevo caso a conductor
                        </a>
                    </div>
                </div>
                
                <table class="proc">
                    <thead>
                        <tr>
                            <th>EXPEDIENTE</th>
                            <th>FECHA FALTA</th>
                            <th>TIPO DE FALTA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($worker->procesos as $p)
                            <tr>
                                <td>
                                    <a class="id" href="{{ route('abogado.detalleproceso', $p->id) }}">{{ $p->numeroExpediente() }}</a>
                                    @if(($p->anexos_count ?? 0) > 0)
                                        <span style="display:block;color:#64748b;font-size:12px;margin-top:3px;">{{ $p->anexos_count }} anexo{{ $p->anexos_count === 1 ? '' : 's' }}</span>
                                    @endif
                                </td>
                                <td>{{ $p->fecha_falta ? \Carbon\Carbon::parse($p->fecha_falta)->format('Y-m-d') : '—' }}</td>
                                <td class="name">{{ $p->tipo_falta ?: 'Sin tipo' }}</td>
                                <td>
                                    @if($p->estado == 'Pendiente')
                                        <span class="st"><i class="fas fa-circle dot-pend"></i> Pendiente</span>
                                    @elseif($p->estado == 'En Proceso')
                                        <span class="st"><i class="fas fa-circle dot-proc"></i> En Proceso</span>
                                    @elseif($p->estado == 'Sancionado')
                                        <span class="st"><i class="fas fa-circle dot-sanc"></i> Sancionado</span>
                                    @else
                                        <span class="st"><i class="fas fa-circle dot-arch"></i> Archivado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="acts">
                                        <a href="{{ route('abogado.detalleproceso', $p->id) }}" title="Ver Expediente"><i class="far fa-eye"></i></a>
                                        <a href="{{ route('documentos.index', $p->id) }}" title="Documentos"><i class="far fa-file-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="casos-pager js-casos-pager" hidden>
                    <span class="js-casos-count"></span>
                    <div class="casos-pager-nav">
                        <button type="button" class="sipd-page js-casos-prev">Anterior</button>
                        <button type="button" class="sipd-page js-casos-next">Siguiente</button>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="table-card">
            @include('partials.paginacion', ['paginador' => $workersGrouped, 'etiqueta' => 'personas'])
        </div>
    @endif
@endsection
