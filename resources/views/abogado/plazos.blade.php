@extends('layouts.master')

@php
    $pageTitle = 'Plazos y términos';
    $codigoPlazo = function ($id) {
        return 'PLZ-' . str_pad($id, 3, '0', STR_PAD_LEFT);
    };
    $codigoProceso = function ($id) {
        return 'PRO-' . str_pad($id, 3, '0', STR_PAD_LEFT);
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Plazos y términos</h1>
            <p>Control de vencimientos y términos procesales.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $conteos['todos'] }} total</span>
            <span class="stat-chip green">{{ $conteos['vigente'] }} Vigentes</span>
            @if($conteos['por_vencer'] > 0)<span class="stat-chip yellow">{{ $conteos['por_vencer'] }} Por vencer</span>@endif
            @if($conteos['vencido'] > 0)<span class="stat-chip red">{{ $conteos['vencido'] }} Vencidos</span>@endif
        </div>
    </div>
@endsection

@section('styles')
<style>
:root {
    --font-main: 'Inter', sans-serif;
    --font-head: 'Inter', sans-serif;
    --p-primary: #3b82f6;
    --p-dark: #0f172a;
    --p-surface: #fff;
    --c-muted: #64748b;
    --radius: 20px;
    --p-shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.08);
    --p-hover: 0 20px 40px -10px rgba(15, 23, 42, 0.12);
    --t-smooth: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}



.kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(12px, 1.6vw, 20px); margin-bottom: 24px; }
.kpi {
    background: var(--p-surface); border-radius: var(--radius); padding: 24px; box-shadow: var(--p-shadow); border-top: 4px solid #e2e8f0; transition: var(--t-smooth); animation: fadeInUp 0.6s ease-out backwards;
}
.kpi:nth-child(2) { animation-delay: 0.1s; }
.kpi:nth-child(3) { animation-delay: 0.2s; }
.kpi:hover { transform: translateY(-5px); box-shadow: var(--p-hover); }

.kpi.vigente { border-top-color: var(--cth-green-bright); background: linear-gradient(180deg, #fff, #f0fdf4 150%); }
.kpi.vencer { border-top-color: #f59e0b; background: linear-gradient(180deg, #fff, #fffbeb 150%); }
.kpi.vencido { border-top-color: #ef4444; background: linear-gradient(180deg, #fff, #fef2f2 150%); }

.kpi strong { display: block; font-family: var(--font-head); font-size: 36px; line-height: 1; margin-bottom: 8px; color: var(--p-dark); }
.kpi span { font-weight: 600; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; }

.chips { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; animation: fadeInUp 0.7s ease-out backwards; }
.chip {
    display: inline-flex; align-items: center; border-radius: 999px; padding: 10px 20px; background: #fff; color: #475569; text-decoration: none; font-size: 14px; font-weight: 600; box-shadow: var(--p-shadow); transition: var(--t-smooth); border: 1px solid var(--cth-border);
}
.chip:hover { border-color: #cbd5e1; color: var(--p-dark); transform: translateY(-2px); box-shadow: var(--p-hover); }
.chip.active { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: var(--cth-green); border-color: #bbf7d0; box-shadow: 0 4px 15px rgba(34,197,94,0.15); }

.table-card { background: #fff; border-radius: var(--radius); overflow-x: auto; box-shadow: var(--p-shadow); animation: fadeInUp 0.8s ease-out backwards; }

table.plz { width: 100%; border-collapse: collapse; border-spacing: 0; }
table.plz th { font-family: var(--font-head); text-align: left; padding: 12px 14px; color: #64748b; font-size: 12px; letter-spacing: 0.1em; font-weight: 700; text-transform: uppercase; border: 1px solid var(--cth-border); border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
table.plz td { padding: 12px 14px; border: 1px solid var(--cth-border); color: var(--p-dark); font-size: 13px; transition: var(--t-smooth); background: #fff; }
table.plz tr:hover td { background: #f8fafc; }

.id:hover { background: #e2e8f0; color: var(--p-dark); }
.pro { color: var(--cth-green); font-weight: 700; text-decoration: none; font-family: var(--font-head); }
.pro:hover { text-decoration: underline; }

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
.acts a.btn-view:hover { background: #ecfdf5; color: var(--cth-green-text); }
.acts button.warning:hover,
.acts button.lock-edit:hover { background: #fffbeb; color: #d97706; }
.acts button.lock-del:hover,
.acts button.danger:hover { background: #fff1f2; color: #e11d48; }
table.plz th.col-acts,
table.plz td.col-acts { text-align: center; width: 1%; white-space: nowrap; }

.name { font-weight: 600; color: var(--p-dark); }
.dias {
    display: inline-block; font-weight: 700; padding: 4px 8px; border-radius: 6px;
}
.dias.ok { color: #15803d; background: #f0fdf4; }
.dias.warn { color: #d97706; background: #fffbeb; }
.dias.late { color: #ef4444; background: #fef2f2; }

.st { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; padding: 6px 12px; border-radius: 12px; white-space: nowrap; }
.st.vigente { background: #f0fdf4; color: var(--cth-green); }
.st.vencer { background: #fffbeb; color: #d97706; }
.st.vencido { background: #fef2f2; color: #dc2626; }

.plz-tipo { display: flex; flex-direction: column; gap: 6px; }
.plz-tipo select {
    font-size: 13px; font-weight: 600; color: var(--p-dark); border: 1px solid var(--cth-border);
    border-radius: 8px; padding: 7px 10px; background: #fff; max-width: 240px;
}
.plz-tipo select:focus { outline: none; border-color: #86efac; box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }

.empty { text-align: center; padding: 40px 16px; color: #94a3b8; }
.pager { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; color: #94a3b8; font-size: 13px; }
.pager-pages { display: flex; gap: 6px; align-items: center; }
.pager a, .pager span.current { min-width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; text-decoration: none; color: #64748b; background: #fff; border: 1px solid var(--cth-border); }
.pager span.current { background: var(--p-primary); border-color: var(--p-primary); color: #fff; font-weight: 700; }
@media (max-width: 900px) { .kpis { grid-template-columns: 1fr; } .table-card { overflow-x: auto; } .proc-head { flex-direction: column; } }
@media (max-width: 1100px) and (min-width: 901px) { .kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
</style>
@endsection

@section('content')
    <section class="kpis">
        <article class="kpi vigente">
            <strong>{{ $conteos['vigente'] }}</strong>
            <span>Vigente</span>
        </article>
        <article class="kpi vencer">
            <strong>{{ $conteos['por_vencer'] }}</strong>
            <span>Por vencer</span>
        </article>
        <article class="kpi vencido">
            <strong>{{ $conteos['vencido'] }}</strong>
            <span>Vencido</span>
        </article>
    </section>

    <div class="chips">
        <a class="chip {{ $filtro === 'todos' ? 'active' : '' }}" href="{{ route('abogado.plazos', array_filter(['per_page' => request('per_page')])) }}">Todos</a>
        <a class="chip {{ $filtro === 'vigente' ? 'active' : '' }}" href="{{ route('abogado.plazos', array_filter(['estado' => 'vigente', 'per_page' => request('per_page')])) }}">Vigente</a>
        <a class="chip {{ $filtro === 'por_vencer' ? 'active' : '' }}" href="{{ route('abogado.plazos', array_filter(['estado' => 'por_vencer', 'per_page' => request('per_page')])) }}">Por vencer</a>
        <a class="chip {{ $filtro === 'vencido' ? 'active' : '' }}" href="{{ route('abogado.plazos', array_filter(['estado' => 'vencido', 'per_page' => request('per_page')])) }}">Vencido</a>
    </div>

    <div class="table-card">
        <table class="plz">
            <thead>
                <tr>
                    <th>PROCESO</th>
                    <th>TRABAJADOR</th>
                    <th>MODALIDAD</th>
                    <th>TIPO DE PLAZO</th>
                    <th>VENCIMIENTO</th>
                    <th>DÍAS RESTANTES</th>
                    <th>ESTADO</th>
                    <th class="col-acts">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plazos as $plazo)
                    <tr>
                        <td>
                            <a class="pro" href="{{ route('abogado.detalleproceso', $plazo->proceso_id) }}">{{ $codigoProceso($plazo->proceso_id) }}</a>
                        </td>
                        <td class="name">{{ $plazo->conductor }}</td>
                        <td>{{ \App\Support\Modalidades::etiquetaCaso($plazo->modalidad, $plazo->cargo) }}</td>
                        <td>
                            @php
                                $puedeEditar = auth()->user()->puede('editar_casos') || auth()->user()->esCoordinadora();
                                $yaVencido = $plazo->semaforo === 'vencido';
                                $editable = (!$plazo->tiene_forma_seteada || $puedeEditar) && !$yaVencido;
                                $soloCoordinadora = in_array($plazo->estado, ['Sancionado', 'Archivado'], true) && !auth()->user()->esCoordinadora();
                            @endphp
                            @if(!empty($plazo->es_descargos) && !$soloCoordinadora)
                                @if($editable)
                                    <form class="plz-tipo" method="POST" action="{{ route('abogado.plazos.descargos', $plazo->proceso_id) }}">
                                        @csrf
                                        @method('PUT')
                                        @if($filtro !== 'todos')
                                            <input type="hidden" name="estado" value="{{ $filtro }}">
                                        @endif
                                        <select name="descargos_presentacion" onchange="this.form.submit()" aria-label="Presentación de descargos">
                                            @if($plazo->descargos_presentacion === 'presentado')
                                                <option value="presentado" selected>Descargos · Presentado</option>
                                            @endif
                                            @foreach($opcionesDescargos as $valor => $etiqueta)
                                                <option value="{{ $valor }}" {{ $plazo->descargos_presentacion === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span style="font-weight: 600; color: #334155;">{{ $opcionesDescargos[$plazo->descargos_presentacion] ?? 'Descargos · No presentó' }}</span>
                                @endif
                            @else
                                {{ $plazo->tipo }}
                            @endif
                        </td>
                        <td>{{ $plazo->vencimiento->format('Y-m-d') }}</td>
                        <td>
                            @php
                                $diasCls = $plazo->semaforo === 'vigente' ? 'ok' : ($plazo->semaforo === 'por_vencer' ? 'warn' : 'late');
                            @endphp
                            <span class="dias {{ $diasCls }}">
                                {{ $plazo->dias }} día{{ abs((int) $plazo->dias) === 1 ? '' : 's' }}
                            </span>
                        </td>
                        <td>
                            @if($plazo->semaforo === 'vigente')
                                <span class="st vigente"><i class="fas fa-circle"></i> Vigente</span>
                            @elseif($plazo->semaforo === 'por_vencer')
                                <span class="st vencer"><i class="fas fa-circle"></i> Por vencer</span>
                            @else
                                <span class="st vencido"><i class="fas fa-circle"></i> Vencido</span>
                            @endif
                        </td>
                        <td class="col-acts">
                            <div class="acts">
                                <a href="{{ route('abogado.detalleproceso', $plazo->proceso_id) }}" class="btn-view" title="Ver Expediente"><i class="far fa-eye"></i></a>
                                
                                @if(!$soloCoordinadora)
                                    @if(!$editable && !$yaVencido)
                                        <button type="button"
                                                class="warning lock-edit"
                                                title="Solicitar permiso para actualizar plazo"
                                                onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('editar_casos')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    @endif

                                    @if(auth()->user()->puede('eliminar_casos'))
                                    <form action="{{ route('abogado.eliminarproceso', $plazo->proceso_id) }}" method="POST"
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
                                            class="danger lock-del"
                                            title="Solicitar permiso para eliminar"
                                            onclick="window.SIPD_abrirPermiso && SIPD_abrirPermiso('eliminar_casos')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty">No hay plazos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @include('partials.paginacion', ['paginador' => $plazos])
    </div>
@endsection
