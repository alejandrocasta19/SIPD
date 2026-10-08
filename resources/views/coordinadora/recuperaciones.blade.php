@extends('layouts.master')

@php $pageTitle = 'Recuperar contraseñas'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Recuperación de contraseñas</h1>
            <p>Al aprobar una solicitud, el sistema envía al correo registrado un enlace de un solo uso para crear una contraseña nueva.</p>
        </div>
        <div class="proc-head-side">
            <span class="stat-chip">{{ $pendientes->total() }} pendiente{{ $pendientes->total() === 1 ? '' : 's' }}</span>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .recovery-card { background:#fff; border:1px solid var(--cth-border); border-radius:18px; padding:20px; margin-bottom:14px; }
    .recovery-top { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .recovery-top b { display:block; color:#0f172a; }
    .recovery-top small { color:#64748b; }
    .recovery-note { color:#475569; font-size:13px; margin-bottom:14px; }
    .recovery-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .recovery-actions button { height:40px; border:0; border-radius:10px; padding:0 14px; font-weight:700; cursor:pointer; }
    .recovery-send { background:var(--cth-green); color:#fff; }
    .recovery-reject { background:#fff1f2; color:#be123c; }
    .recovery-history { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; border-bottom:1px solid var(--cth-line); padding:12px 4px; color:#64748b; font-size:13px; }
    .recovery-history b { color:#334155; }
    .recovery-empty { text-align:center; color:#94a3b8; padding:30px 10px; }
    .recovery-history-title { margin:26px 0 8px; font-size:16px; font-weight:700; }
</style>
@endsection

@section('content')
    @forelse($pendientes as $solicitud)
        <article class="recovery-card">
            <div class="recovery-top">
                <div>
                    <b>{{ $solicitud->user->name }}</b>
                    <small>{{ $solicitud->user->email }} · {{ $solicitud->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <span class="stat-chip yellow">Pendiente</span>
            </div>
            <p class="recovery-note">El enlace de restablecimiento se enviará al correo que ya está registrado. No se muestra ni se comparte ninguna contraseña.</p>
            <form class="recovery-actions" method="POST" action="{{ route('coordinadora.recuperaciones.responder', $solicitud->id) }}">
                @csrf
                @method('PUT')
                <button type="submit" name="accion" value="enviar_enlace" class="recovery-send"
                        data-confirm="Se enviará al correo registrado un enlace de un solo uso para crear una contraseña nueva."
                        data-confirm-title="Aprobar recuperación"
                        data-confirm-ok="Enviar enlace">Aprobar y enviar enlace</button>
                <button type="submit" name="accion" value="rechazar" class="recovery-reject"
                        data-confirm="La solicitud quedará rechazada. La persona podrá volver a solicitarla."
                        data-confirm-title="Rechazar solicitud"
                        data-confirm-ok="Rechazar"
                        data-confirm-danger="1">Rechazar</button>
            </form>
        </article>
    @empty
        <div class="recovery-card recovery-empty">No hay solicitudes pendientes de recuperación.</div>
    @endforelse

    @include('partials.paginacion', ['paginador' => $pendientes, 'etiqueta' => 'solicitudes pendientes'])

    @if($historial->total() > 0)
        <h2 class="recovery-history-title">Historial</h2>
        @foreach($historial as $solicitud)
            <div class="recovery-history">
                <div>
                    <b>{{ $solicitud->user->name }}</b> · {{ $solicitud->user->email }} ·
                    {{ $solicitud->estado === \App\Models\RecuperacionContrasena::ENLACE_ENVIADO ? 'Enlace enviado' : 'Rechazada' }}
                    @if($solicitud->respondente) · {{ $solicitud->respondente->name }} @endif
                </div>
                <time>{{ optional($solicitud->responded_at)->format('d/m/Y H:i') }}</time>
            </div>
        @endforeach
        @include('partials.paginacion', ['paginador' => $historial, 'etiqueta' => 'solicitudes resueltas'])
    @endif
@endsection
