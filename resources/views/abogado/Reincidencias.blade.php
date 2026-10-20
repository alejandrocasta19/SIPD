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
            <span class="stat-chip yellow">{{ $reincidentes }} reincidentes</span>
            <a class="btn-add" href="{{ route('abogado.registro') }}"><i class="fas fa-plus"></i> Registrar proceso</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .table-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 24px; }
    .worker-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--cth-line); display: flex; justify-content: space-between; align-items: center; }
    .worker-info { flex: 1; }
    .worker-info b { font-size: 16px; color: #0f172a; display: block; margin-bottom: 2px; }
    .worker-info span { font-size: 13px; color: #64748b; }
    
    .st-reincidente { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; border-radius: 999px; background: #fff1f2; color: #e11d48; margin-right: 12px; }
    .st-normal { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; border-radius: 999px; background: #f0fdf4; color: var(--cth-green-text); margin-right: 12px; }

    table.proc { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.proc th { text-align: left; padding: 10px 20px; color: #64748b; font-size: 11px; letter-spacing: .06em; font-weight: 700; border: 1px solid var(--cth-border); border-bottom: 2px solid #e2e8f0; white-space: nowrap; text-transform: uppercase; background: #f8fafc; }
    table.proc td { padding: 10px 20px; border: 1px solid var(--cth-border); color: #334155; vertical-align: middle; background: #fff; }
    table.proc tbody tr:hover td { background: #fafbfc; }
    table.proc tr.st-pendiente td:first-child { border-left: 3px solid #f59e0b; }
    table.proc tr.st-en-proceso td:first-child { border-left: 3px solid #3b82f6; }
    table.proc tr.st-sancionado td:first-child { border-left: 3px solid #f43f5e; }
    table.proc tr.st-archivado td:first-child { border-left: 3px solid #94a3b8; }

    .id { color: var(--cth-green); font-weight: 700; text-decoration: none; }
    .name { font-weight: 600; color: #0f172a; }
    .st { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; }
    .st i { font-size: 8px; }
    
    .dot-pend { color: #f59e0b; }
    .dot-proc { color: #3b82f6; }
    .dot-sanc { color: #f43f5e; }
    .dot-arch { color: #94a3b8; }

    .acts { display: flex; gap: 8px; justify-content: center; align-items: center; }
    .acts a,
    .acts button {
        width: 30px;
        height: 30px;
        border: 0;
        background: #f1f5f9;
        color: #64748b;
        border-radius: 8px;
        display: grid;
        place-items: center;
        text-decoration: none;
        cursor: pointer;
        font-size: 13px;
        padding: 0;
        flex-shrink: 0;
    }
    .acts a:hover,
    .acts button:hover { background: #e2e8f0; color: #0f172a; }
    .acts a.btn-view:hover { background: #dcfce7; color: #15803d; }
    .acts button.danger:hover { background: #fee2e2; color: #b91c1c; }
    table.proc th.col-acts,
    table.proc td.col-acts { text-align: center; width: 1%; white-space: nowrap; }

    .empty { text-align: center; padding: 40px 16px; color: #94a3b8; background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    
    .pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; color: #94a3b8; font-size: 13px; border-top: 1px solid var(--cth-line); }
    .pager-pages { display: flex; gap: 6px; align-items: center; }
    .pager a, .pager span.current { min-width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; text-decoration: none; color: #64748b; background: #fff; border: 1px solid var(--cth-border); }
    .pager span.current { background: var(--cth-green-bright); border-color: var(--cth-green-bright); color: #fff; font-weight: 700; }
    
    .global-pager-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 24px; }
    .casos-pager {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-top: 1px solid var(--cth-line);
        color: #94a3b8;
        font-size: 12px;
    }
    .casos-pager[hidden] { display: none; }
    .casos-pager-nav {
        display: flex;
        gap: 5px;
        align-items: center;
        justify-self: center;
    }
    .casos-page {
        min-width: 30px;
        height: 30px;
        padding: 0 8px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font: inherit;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--cth-border);
        background: #fff;
        color: #64748b;
        transition: background .12s, color .12s;
    }
    .casos-page:hover:not(:disabled) { background: #ecfdf5; color: var(--cth-green-text); border-color: #bbf7d0; }
    .casos-page.is-current { background: var(--cth-green); border-color: var(--cth-green); color: #fff; cursor: default; }
    .casos-page:disabled { opacity: .4; cursor: default; pointer-events: none; }

    @media (max-width: 900px) { .toolbar { grid-template-columns: 1fr; } .proc-head { flex-direction: column; } .table-card { overflow-x: auto; } }
</style>
@endsection

@section('scripts')
<script>
(function () {
    document.querySelectorAll('.js-casos-card').forEach(function (card) {
        var rows = Array.prototype.slice.call(card.querySelectorAll('tbody tr'));
        var nav  = card.querySelector('.js-casos-pager');
        var perPage = Math.max(1, parseInt(card.getAttribute('data-per-page'), 10) || 5);
        var page = 1;
        var total = rows.length;
        if (!nav || total === 0) return;

        function render() {
            var pages = Math.max(1, Math.ceil(total / perPage));
            if (page < 1) page = 1;
            if (page > pages) page = pages;
            var from = (page - 1) * perPage;
            var to   = Math.min(from + perPage, total);

            // Mostrar/ocultar filas
            rows.forEach(function (row, i) { row.hidden = i < from || i >= to; });

            // Paginador: solo si hay más de una página
            var needsPager = total > perPage;
            nav.hidden = !needsPager;
            if (!needsPager) return;

            // Botones de número de página
            var numbersWrap = nav.querySelector('.js-casos-numbers');
            if (numbersWrap) {
                numbersWrap.innerHTML = '';
                var desde = Math.max(1, page - 2);
                var hasta = Math.min(pages, page + 2);
                for (var n = desde; n <= hasta; n++) {
                    (function (num) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'casos-page' + (num === page ? ' is-current' : '');
                        btn.textContent = num;
                        if (num !== page) {
                            btn.addEventListener('click', function () { page = num; render(); });
                        }
                        numbersWrap.appendChild(btn);
                    })(n);
                }
            }

            // Prev / Next
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
        <div class="search">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nombre del trabajador o cédula...">
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
            <div class="table-card js-casos-card" data-per-page="5">
                <div class="worker-header">
                    <div class="worker-info">
                        <b>{{ $worker->nombre }}</b>
                        <span>
                            @if($worker->cedula) C.C. {{ $worker->cedula }} · @endif
                            {{ $worker->modalidades->isNotEmpty() ? $worker->modalidades->implode(' · ') : 'Sin modalidad' }}
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
                            Nuevo caso al trabajador
                        </a>
                    </div>
                </div>
                
                <table class="proc">
                    <thead>
                        <tr>
                            <th>EXPEDIENTE</th>
                            <th>FECHA FALTA</th>
                            <th>MODALIDAD</th>
                            <th>TIPO DE FALTA</th>
                            <th>ESTADO</th>
                            <th class="col-acts">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($worker->procesos as $p)
                            @php
                                $stClass = match($p->estado) {
                                    'Pendiente'  => 'st-pendiente',
                                    'En Proceso' => 'st-en-proceso',
                                    'Sancionado' => 'st-sancionado',
                                    default      => 'st-archivado',
                                };
                            @endphp
                            <tr class="{{ $stClass }}">
                                <td>
                                    <a class="id" href="{{ route('abogado.detalleproceso', $p->id) }}">{{ $p->numeroExpediente() }}</a>
                                    @if(($p->anexos_count ?? 0) > 0)
                                        <span style="display:block;color:#64748b;font-size:12px;margin-top:3px;">{{ $p->anexos_count }} anexo{{ $p->anexos_count === 1 ? '' : 's' }}</span>
                                    @endif
                                </td>
                                <td>{{ $p->fecha_falta ? \Carbon\Carbon::parse($p->fecha_falta)->format('Y-m-d') : '—' }}</td>
                                <td>{{ \App\Support\Modalidades::etiquetaCaso($p->modalidad, $p->cargo) }}</td>
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
                                <td class="col-acts">
                                    <div class="acts">
                                        <a href="{{ route('abogado.detalleproceso', $p->id) }}" class="btn-view" title="Ver Expediente"><i class="far fa-eye"></i></a>
                                        @if(!$p->soloLoManejaCoordinadora())
                                            @if(auth()->user()->puede('eliminar_casos'))
                                            <form action="{{ route('abogado.eliminarproceso', $p->id) }}" method="POST"
                                                  data-confirm="Esta acción eliminará el proceso disciplinario de forma permanente."
                                                  data-confirm-title="Eliminar proceso"
                                                  data-confirm-ok="Eliminar"
                                                  data-confirm-danger="1"
                                                  data-confirm-icon="warning">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="danger" title="Eliminar proceso">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            @else
                                            <button type="button"
                                                    class="danger"
                                                    title="Solicitar permiso para eliminar"
                                                    onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('eliminar_casos')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="casos-pager js-casos-pager" hidden>
                    <div class="casos-pager-nav">
                        <button type="button" class="casos-page js-casos-prev">Anterior</button>
                        <span class="js-casos-numbers" style="display:contents"></span>
                        <button type="button" class="casos-page js-casos-next">Siguiente</button>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="table-card">
            @include('partials.paginacion', ['paginador' => $workersGrouped, 'etiqueta' => 'personas'])
        </div>
    @endif
@endsection
