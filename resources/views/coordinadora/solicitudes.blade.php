@extends('layouts.master')

@php $pageTitle = 'Solicitudes de permiso'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Solicitudes de permiso</h1>
            <p>El equipo indica qué hará y por qué. Tú eliges el módulo, la función y las horas. Descargar se otorga aparte de ver.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $pendientes->count() }} pendiente{{ $pendientes->count() === 1 ? '' : 's' }}</span>
            <a class="btn-alt" href="{{ route('coordinadora.notificar') }}"><i class="far fa-paper-plane"></i> Avisar al equipo</a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .sol-card { background:#fff; border: 1px solid var(--cth-border); border-radius:18px; padding:20px; margin-bottom:16px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .sol-top { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
    .sol-top b { display:block; color:#0f172a; }
    .sol-top small { color:#64748b; }
    .sol-body { display:grid; gap:8px; margin-bottom:16px; font-size:14px; color:#334155; }
    .sol-body span { color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .sol-form { display:grid; grid-template-columns: minmax(0,1.2fr) 140px 110px auto auto; gap:8px; align-items:end; }
    .sol-form label { display:block; font-size:11px; font-weight:700; color:#94a3b8; margin-bottom:4px; }
    .sol-form select, .sol-form input, .sol-form textarea {
        width:100%; height:40px; border: 1px solid var(--cth-border); border-radius:10px; padding:0 10px; font:inherit; font-size:13px;
    }
    .sol-form textarea { height:40px; padding:8px 10px; }
    .sol-form .custom { display:none; }
    .sol-form.is-custom .custom { display:block; }
    .sol-form button { height:40px; border:0; border-radius:10px; padding:0 14px; font-weight:700; cursor:pointer; }
    .ok { background:var(--cth-green); color:#fff; }
    .no { background:#fff1f2; color:#e11d48; }
    .hist {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        color:#64748b;
        font-size:13px;
        padding:10px 4px 10px 0;
        border-bottom: 1px solid var(--cth-line);
    }
    .hist b { color:#334155; }
    .hist-del {
        border:0; background:#f8fafc; color:#64748b;
        width:34px; height:34px; border-radius:10px; cursor:pointer; flex-shrink:0;
    }
    .hist-del:hover { background:#fee2e2; color:#be123c; }
    .hist-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:24px 0 12px; }
    .hist-head h3 { margin:0; font-size:16px; }
    .empty { text-align:center; color:#94a3b8; padding:32px 8px; }
    @media (max-width: 900px) { .sol-form { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
    @forelse($pendientes as $solicitud)
        <article class="sol-card">
            <div class="sol-top">
                <div>
                    <b>{{ $solicitud->user->name }}</b>
                    <small>{{ $solicitud->created_at->format('d/m/Y H:i') }} · pidió {{ $solicitud->horas }} hora{{ $solicitud->horas === 1 ? '' : 's' }}</small>
                </div>
                <span class="stat-chip yellow">{{ $solicitud->etiquetaPermiso() }}</span>
            </div>
            <div class="sol-body">
                <div><span>Qué va a hacer</span><div>{{ $solicitud->que_hara }}</div></div>
                <div><span>Por qué</span><div>{{ $solicitud->motivo }}</div></div>
            </div>
            <form class="sol-form" method="POST" action="{{ route('coordinadora.solicitudes.responder', $solicitud->id) }}" data-grant>
                @csrf
                @method('PUT')
                <div>
                    <label>Módulo y función</label>
                    <select name="permiso">
                        @foreach(\App\Support\RhPermisos::solicitablesAgrupados() as $grupo)
                            <optgroup label="{{ $grupo['label'] }}">
                                @foreach($grupo['items'] as $clave => $etiqueta)
                                    <option value="{{ $clave }}" {{ $solicitud->permiso === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Tiempo</label>
                    <select name="duracion" class="dur">
                        @foreach($duraciones as $valor => $texto)
                            <option value="{{ $valor }}" {{ (string) $solicitud->horas === (string) $valor ? 'selected' : '' }}>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="custom">
                    <label>Horas</label>
                    <input type="number" name="horas_custom" min="1" max="168" value="{{ in_array((string) $solicitud->horas, ['1','3','5'], true) ? '' : $solicitud->horas }}">
                </div>
                <button type="submit" name="accion" value="otorgar" class="ok">Otorgar</button>
                <button type="submit" name="accion" value="rechazar" class="no"
                        data-confirm="La solicitud quedará rechazada."
                        data-confirm-title="Rechazar permiso"
                        data-confirm-ok="Rechazar"
                        data-confirm-danger="1">Rechazar</button>
            </form>
        </article>
    @empty
        <div class="sol-card empty">No hay solicitudes pendientes.</div>
    @endforelse
    @include('partials.paginacion', ['paginador' => $pendientes, 'etiqueta' => 'solicitudes pendientes'])

    @if($historial->count() > 0)
        <div class="hist-head">
            <h3>Historial</h3>
            <form method="POST" action="{{ route('coordinadora.solicitudes.historial') }}"
                  data-confirm="Se borrará el historial de solicitudes ya resueltas. Las pendientes no se tocan."
                  data-confirm-title="Vaciar historial"
                  data-confirm-ok="Vaciar"
                  data-confirm-danger="1">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-alt">
                    <i class="far fa-trash-alt"></i> Vaciar historial
                </button>
            </form>
        </div>
        @foreach($historial as $item)
            <div class="hist">
                <div>
                    <b>{{ $item->user->name }}</b> · {{ $item->etiquetaPermiso() }} ·
                    {{ $item->estado === 'otorgada' ? 'Otorgada' : 'Rechazada' }}
                    ({{ $item->horas }} h)
                    @if($item->respondente) · {{ $item->respondente->name }} @endif
                    · {{ optional($item->responded_at)->format('d/m/Y H:i') }}
                </div>
                <form method="POST" action="{{ route('coordinadora.solicitudes.destroy', $item->id) }}"
                      data-confirm="Se quitará este registro del historial."
                      data-confirm-title="Eliminar del historial"
                      data-confirm-ok="Eliminar"
                      data-confirm-danger="1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="hist-del" title="Eliminar"><i class="far fa-trash-alt"></i></button>
                </form>
            </div>
        @endforeach
        @include('partials.paginacion', ['paginador' => $historial])
    @endif
@endsection

@section('scripts')
<script>
    document.querySelectorAll('form[data-grant]').forEach(function (form) {
        var sel = form.querySelector('.dur');
        function sync() { form.classList.toggle('is-custom', sel.value === 'custom'); }
        sel.addEventListener('change', sync);
        if (!['1','3','5'].includes(sel.value)) {
            sel.value = 'custom';
        }
        sync();
    });
</script>
@endsection
