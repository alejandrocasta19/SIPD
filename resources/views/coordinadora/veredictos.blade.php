@extends('layouts.master')

@php
    $pageTitle = 'Veredictos';
    $codigo = function ($proceso) {
        return 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT);
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Veredictos</h1>
            <p>Registra sanción, archivo o cancelación por justa causa con su justificación.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $procesos->total() }} pendiente{{ $procesos->total() === 1 ? '' : 's' }}</span>
            <a class="btn-alt" href="{{ route('abogado.consultarproceso') }}"><i class="fas fa-search"></i> Todos los procesos</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .ver-card { background:#fff; border: 1px solid var(--cth-border); border-radius:18px; box-shadow:0 1px 2px rgba(15,23,42,.04); overflow:hidden; }
    .ver-row { display:grid; grid-template-columns: 88px minmax(0,1.4fr) minmax(0,1fr); gap:16px; align-items:center; padding:18px 20px; border-bottom: 1px solid var(--cth-line); }
    .ver-row:last-child { border-bottom:0; }
    .ver-code { font-weight:800; color:var(--cth-green); text-decoration:none; }
    .ver-main b { display:block; color:#0f172a; font-size:15px; }
    .ver-main small { color:#64748b; font-size:12px; }
    .ver-rh { color:#475569; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .ver-acts { grid-column:1/-1; display:grid; grid-template-columns:1fr; gap:10px; padding:14px; border:1px solid #e2e8f0; border-radius:14px; background:#f8fafc; }
    .ver-acts form { display:grid; grid-template-columns:minmax(180px,.8fr) minmax(190px,1fr) minmax(260px,1.6fr) auto; gap:12px; align-items:end; }
    .ver-field { display:grid; gap:6px; min-width:0; }
    .ver-field > span { color:#475569; font-size:11px; font-weight:800; }
    .ver-field select, .ver-field input, .ver-field textarea { width:100%; min-height:40px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; color:#0f172a; font:inherit; font-size:13px; }
    .ver-field textarea { min-height:40px; height:40px; resize:vertical; }
    .ver-days[hidden] { display:none; }
    .ver-acts-btns { display:flex; gap:8px; align-items:flex-end; }
    .ver-acts button { min-height:40px; border:0; border-radius:10px; padding:9px 14px; background:#006837; color:#fff; font-weight:800; font-size:12px; cursor:pointer; white-space:nowrap; }
    .ver-acts button:hover { background:#004f2b; }
    .ver-view { display:inline-flex; align-items:center; gap:6px; min-height:40px; padding:9px 16px; border:2px solid #006837; border-radius:10px; color:#006837; font-size:12px; font-weight:800; text-decoration:none; white-space:nowrap; transition:background .15s,color .15s; }
    .ver-view:hover { background:#006837; color:#fff; }
    .ver-view i { font-size:13px; }
    .empty { text-align:center; color:#94a3b8; padding:48px 16px; }
    .pager { display:flex; justify-content:space-between; padding:14px 20px; color:#64748b; font-size:13px; }
    @media (max-width: 900px) {
        .ver-row { grid-template-columns:1fr; }
        .ver-acts form { grid-template-columns:1fr 1fr; }
    }
    @media (max-width: 560px) {
        .ver-acts form { grid-template-columns:1fr; }
        .ver-acts button { width:100%; }
    }
</style>
@endsection

@section('content')
    <div class="ver-card">
        @forelse($procesos as $proceso)
            <div class="ver-row">
                <a class="ver-code" href="{{ route('abogado.detalleproceso', $proceso->id) }}">{{ $codigo($proceso) }}</a>
                <div class="ver-main">
                    <b>{{ $proceso->nombre }}</b>
                    <small>{{ \App\Support\Modalidades::etiquetaCaso($proceso->modalidad, $proceso->cargo) }} · {{ $proceso->tipo_falta ?: 'Sin tipificar' }} · En Proceso</small>
                </div>
                <div class="ver-rh">{{ $proceso->user->name ?? $proceso->user_name_snapshot ?? 'Sin RH asignado' }}</div>
                <div class="ver-acts">
                    <form id="veredicto-form-{{ $proceso->id }}" action="{{ route('abogado.actualizarestado', $proceso->id) }}" method="POST"
                          data-confirm="El veredicto será definitivo y se notificará al responsable para comunicar el resultado al trabajador."
                          data-confirm-title="Registrar veredicto"
                          data-confirm-ok="Registrar"
                          data-confirm-danger="1"
                          data-confirm-icon="warning">
                        @csrf
                        @method('PUT')
                        <label class="ver-field">
                            <span>Resultado del veredicto</span>
                            <select name="veredicto_tipo" required data-veredicto-select>
                                <option value="">Selecciona una opción</option>
                                <option value="sancion">Sanción</option>
                                <option value="archivo">Archivar el caso</option>
                                <option value="terminacion_justa_causa">Cancelación de contrato por justa causa</option>
                            </select>
                        </label>
                        <label class="ver-field ver-days" hidden>
                            <span>Días de sanción</span>
                            <input type="number" name="dias_sancion" min="1" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="Ej. 5" disabled data-veredicto-days>
                        </label>
                        <label class="ver-field">
                            <span>Justificación <small data-veredicto-justification-label style="font-weight:400;color:#94a3b8">(opcional)</small></span>
                            <textarea name="decision_final" rows="1" maxlength="5000" placeholder="Escribe la justificación del resultado" data-veredicto-justification></textarea>
                        </label>
                        <div class="ver-acts-btns">
                            <a class="ver-view" href="{{ route('abogado.detalleproceso', $proceso->id) }}"><i class="fas fa-folder-open"></i> Ver expediente</a>
                            <button type="submit">Registrar veredicto</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty">No hay casos esperando veredicto.</div>
        @endforelse

        @include('partials.paginacion', ['paginador' => $procesos])
    </div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('[data-veredicto-select]').forEach(function (select) {
        var form = select.form;
        if (!form) return;
        var daysWrap = form.querySelector('.ver-days');
        var daysInput = form.querySelector('[data-veredicto-days]');
        var justification = form.querySelector('[data-veredicto-justification]');
        var justificationLabel = form.querySelector('[data-veredicto-justification-label]');
        function syncVerdictFields() {
            var isSanction = select.value === 'sancion';
            daysWrap.hidden = !isSanction;
            daysInput.disabled = !isSanction;
            daysInput.required = isSanction;
            justification.required = isSanction;
            justificationLabel.textContent = isSanction ? '(obligatoria)' : '(opcional)';
        }
        select.addEventListener('change', syncVerdictFields);
        syncVerdictFields();
    });
</script>
@endsection
