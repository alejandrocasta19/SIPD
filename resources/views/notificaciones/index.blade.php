@extends('layouts.master')

@php $pageTitle = 'Notificaciones'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Bandeja de notificaciones</h1>
            <p>Los avisos de coordinación llegan aparte de las alertas del sistema.</p>
        </div>
        @if($avisos->total() > 0)
            <div class="proc-head-side">
                @if($puedeBorrar)
                    <form method="POST" action="{{ route('notificaciones.leidas') }}"
                          data-confirm="Se borrarán las notificaciones que ya leíste."
                          data-confirm-title="Eliminar leídas"
                          data-confirm-ok="Eliminar"
                          data-confirm-danger="1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-alt">
                            <i class="far fa-trash-alt"></i> Eliminar leídas
                        </button>
                    </form>
                @else
                    <button type="button" class="btn-alt"
                            data-need-permiso="eliminar_notificaciones"
                            data-que="Borrar notificaciones de la bandeja que ya no necesito.">
                        <i class="far fa-trash-alt"></i> Solicitar borrar
                    </button>
                @endif
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    .av-list { display:grid; gap:12px; }
    .av-item {
        display:grid;
        grid-template-columns: 1fr auto;
        gap:12px;
        align-items:stretch;
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:16px;
        overflow:hidden;
        box-shadow:0 1px 2px rgba(15,23,42,.04);
    }
    .av-item.is-unread { box-shadow:0 0 0 1px #bbf7d0 inset; }
    .av-item.is-coord {
        border-color:#f6d48a;
        background:linear-gradient(180deg, #fffbeb 0%, #fff 42%);
    }
    .av-item.is-coord.is-unread { box-shadow:0 0 0 1px #f59e0b inset; }
    .av-main {
        display:grid;
        grid-template-columns: 44px 1fr;
        gap:14px;
        align-items:start;
        padding:16px 18px;
        text-decoration:none;
        color:inherit;
        min-width:0;
    }
    .av-main b { display:block; color:#0f172a; font-size:15px; }
    .av-meta { color:#64748b; font-size:12px; margin-top:4px; }
    .av-motivo {
        margin-top:8px;
        font-size:13px;
        color:#334155;
        background:#f8fafc;
        border-radius:10px;
        padding:10px 12px;
    }
    .av-item.is-coord .av-motivo { background:#fff7ed; color:#9a3412; }
    .dot {
        width:40px; height:40px; border-radius:12px;
        display:grid; place-items:center; background:#eff6ff; color:#2563eb;
    }
    .dot.coord { background:#fff7ed; color:#c2410c; }
    .dot.ok { background:#f0fdf4; color:#15803d; }
    .dot.warn { background:#fef2f2; color:#be123c; }
    .dot.info { background:#eff6ff; color:#1d4ed8; }
    .av-chip {
        display:inline-flex;
        align-items:center;
        gap:6px;
        font-size:10px;
        font-weight:800;
        letter-spacing:.06em;
        text-transform:uppercase;
        border-radius:999px;
        padding:3px 8px;
        margin-bottom:6px;
        background:#e2e8f0;
        color:#475569;
    }
    .av-chip.coord { background:#ffedd5; color:#9a3412; }
    .av-chip.ok { background:#dcfce7; color:#166534; }
    .av-chip.warn { background:#fee2e2; color:#9f1239; }
    .av-side {
        display:flex;
        flex-direction:column;
        align-items:flex-end;
        justify-content:space-between;
        padding:14px 14px 14px 0;
        gap:8px;
    }
    .av-state { font-size:11px; font-weight:700; color:#94a3b8; }
    .av-item.is-unread .av-state { color:#15803d; }
    .av-del {
        border:0; background:#f8fafc; color:#64748b;
        width:34px; height:34px; border-radius:10px; cursor:pointer;
    }
    .av-del:hover { background:#fee2e2; color:#be123c; }
    .empty { text-align:center; color:#94a3b8; padding:48px 16px; background:#fff; border-radius:16px; }
    @media (max-width:700px) {
        .av-item { grid-template-columns:1fr; }
        .av-side { flex-direction:row; padding:0 14px 14px; align-items:center; }
    }
</style>
@endsection

@section('content')
    <div class="av-list">
        @forelse($avisos as $aviso)
            <article class="av-item {{ $aviso->leida_at ? '' : 'is-unread' }} {{ $aviso->esDirectiva() ? 'is-coord' : '' }}">
                <a class="av-main" href="{{ route('notificaciones.leer', $aviso->id) }}">
                    <span class="dot {{ $aviso->tonoIcono() }}"><i class="fas {{ $aviso->icono() }}"></i></span>
                    <div>
                        <span class="av-chip {{ $aviso->esDirectiva() ? 'coord' : $aviso->tonoIcono() }}">{{ $aviso->etiquetaTipo() }}</span>
                        <b>{{ $aviso->titulo }}</b>
                        <div class="av-meta">
                            {{ $aviso->remitente->name ?? 'SIPD' }}
                            · {{ $aviso->created_at->format('d/m/Y H:i') }}
                            @if($aviso->proceso)
                                · PRO-{{ str_pad($aviso->proceso->id, 3, '0', STR_PAD_LEFT) }}
                            @endif
                        </div>
                        @if($aviso->motivo)
                            <div class="av-motivo">{{ $aviso->motivo }}</div>
                        @endif
                        @if($aviso->cuerpo && $aviso->cuerpo !== $aviso->motivo)
                            <div class="av-meta" style="margin-top:8px;">{{ $aviso->cuerpo }}</div>
                        @endif
                    </div>
                </a>
                <div class="av-side">
                    <small class="av-state">{{ $aviso->leida_at ? 'Leída' : 'Nueva' }}</small>
                    @if($puedeBorrar)
                        <form method="POST" action="{{ route('notificaciones.destroy', $aviso->id) }}"
                              data-confirm="Esta notificación se quitará de tu bandeja."
                              data-confirm-title="Eliminar notificación"
                              data-confirm-ok="Eliminar"
                              data-confirm-danger="1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="av-del" title="Eliminar"><i class="far fa-trash-alt"></i></button>
                        </form>
                    @else
                        <button type="button" class="av-del" title="Solicitar permiso para borrar"
                                data-need-permiso="eliminar_notificaciones"
                                data-que="Borrar notificaciones de la bandeja que ya no necesito.">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty">No hay notificaciones.</div>
        @endforelse
    </div>
    @if($avisos->hasPages())
        <div style="margin-top:16px;">{{ $avisos->links() }}</div>
    @endif
@endsection
