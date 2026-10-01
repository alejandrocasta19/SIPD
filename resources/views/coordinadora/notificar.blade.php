@extends('layouts.master')

@php $pageTitle = 'Avisar al equipo'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Avisar al equipo</h1>
            <p>Llega como aviso de coordinación, no como una alerta automática del sistema.</p>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .nt-layout { display:grid; grid-template-columns: minmax(0,1.2fr) minmax(280px,.8fr); gap:20px; align-items:start; }
    .nt-card, .nt-preview {
        background:#fff; border: 1px solid var(--cth-border); border-radius:18px;
        padding:24px; box-shadow:0 1px 2px rgba(15,23,42,.04);
    }
    .nt-banner {
        display:flex; gap:12px; align-items:flex-start;
        background:#fff7ed; border:1px solid #fed7aa; color:#9a3412;
        border-radius:12px; padding:12px 14px; margin-bottom:18px; font-size:13px;
    }
    .nt-banner i { margin-top:2px; }
    .nt-card label { display:block; font-size:13px; font-weight:600; color:#64748b; margin:0 0 6px; }
    .nt-card input[type="text"], .nt-card select, .nt-card textarea {
        width:100%; border: 1px solid var(--cth-border); border-radius:10px; padding:10px 12px; margin-bottom:14px; font:inherit;
    }
    .who-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .who-head button { border:0; background:none; color:var(--cth-green); font-weight:700; font-size:12px; cursor:pointer; }
    .who-list { display:grid; grid-template-columns:1fr 1fr; gap:8px 16px; margin-bottom:16px; }
    .who-list label { display:flex; align-items:center; gap:8px; font-weight:500; color:#0f172a; margin:0; }
    .who-list input { width:auto; margin:0; }
    .nt-actions { display:flex; justify-content:flex-end; gap:8px; }
    .pv-kicker {
        display:inline-flex; align-items:center; gap:6px;
        font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
        background:#ffedd5; color:#9a3412; border-radius:999px; padding:4px 8px; margin-bottom:10px;
    }
    .pv-card {
        border:1px solid #f6d48a; background:linear-gradient(180deg,#fffbeb 0%,#fff 55%);
        border-radius:14px; padding:14px;
    }
    .pv-card b { display:block; font-size:15px; color:#0f172a; }
    .pv-card small { display:block; color:#64748b; margin-top:4px; }
    .pv-motivo { margin-top:10px; background:#fff7ed; color:#9a3412; border-radius:10px; padding:10px 12px; font-size:13px; }
    .pv-empty { color:#94a3b8; font-size:13px; }
    @media (max-width:900px) { .nt-layout { grid-template-columns:1fr; } .who-list { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
    <div class="nt-layout">
        <form class="nt-card" method="POST" action="{{ route('coordinadora.notificar.enviar') }}" id="formAvisoEquipo">
            @csrf
            <div class="nt-banner">
                <i class="fas fa-paper-plane"></i>
                <div>El integrante lo verá etiquetado como <b>aviso de coordinación</b>, con tu nombre y el motivo. No se mezcla con plazos o permisos.</div>
            </div>
            <div class="who-head">
                <label style="margin:0;">Destinatarios</label>
                <button type="button" id="toggleTodos">Seleccionar todos</button>
            </div>
            <div class="who-list" id="listaDestinatarios">
                @forelse($equipo as $miembro)
                    <label>
                        <input type="checkbox" name="destinatarios[]" value="{{ $miembro->id }}" {{ collect(old('destinatarios'))->contains($miembro->id) ? 'checked' : '' }}>
                        {{ $miembro->name }}
                    </label>
                @empty
                    <span>No hay integrantes de RH.</span>
                @endforelse
            </div>
            <label>Asunto</label>
            <input type="text" name="titulo" id="av-titulo" value="{{ old('titulo') }}" required maxlength="160" placeholder="Ej. Revisar descargos de PRO-027">
            <label>Motivo</label>
            <textarea name="motivo" id="av-motivo" rows="3" required maxlength="500" placeholder="Por qué se les avisa.">{{ old('motivo') }}</textarea>
            <label>Instrucción (opcional)</label>
            <textarea name="cuerpo" id="av-cuerpo" rows="4" maxlength="2000" placeholder="Qué deben hacer o el contexto adicional.">{{ old('cuerpo') }}</textarea>
            <label>Expediente relacionado (opcional)</label>
            <select name="proceso_id" id="av-proceso">
                <option value="">Ninguno</option>
                @foreach($procesos as $proceso)
                    <option value="{{ $proceso->id }}" {{ (string) old('proceso_id') === (string) $proceso->id ? 'selected' : '' }}>
                        PRO-{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }} · {{ $proceso->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="nt-actions">
                <a class="btn-ghost" href="{{ route('abogado.dashboard') }}">Cancelar</a>
                <button type="submit" class="btn-ok">Enviar aviso de coordinación</button>
            </div>
        </form>

        <aside class="nt-preview">
            <label style="display:block;font-size:13px;font-weight:600;color:#64748b;margin-bottom:10px;">Así lo verá el equipo</label>
            <div class="pv-card">
                <span class="pv-kicker"><i class="fas fa-paper-plane"></i> Aviso de coordinación</span>
                <b id="pv-titulo">Asunto del aviso</b>
                <small>{{ auth()->user()->name }}</small>
                <div class="pv-motivo" id="pv-motivo">El motivo aparece destacado.</div>
                <small id="pv-cuerpo" class="pv-empty" style="margin-top:8px;"></small>
            </div>
        </aside>
    </div>
@endsection

@section('scripts')
<script>
    (function () {
        var todos = document.getElementById('toggleTodos');
        var boxes = document.querySelectorAll('#listaDestinatarios input[type="checkbox"]');
        if (todos) {
            todos.addEventListener('click', function () {
                var marcar = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
                boxes.forEach(function (b) { b.checked = marcar; });
                todos.textContent = marcar ? 'Quitar todos' : 'Seleccionar todos';
            });
        }
        function syncPreview() {
            var titulo = document.getElementById('av-titulo');
            var motivo = document.getElementById('av-motivo');
            var cuerpo = document.getElementById('av-cuerpo');
            document.getElementById('pv-titulo').textContent = (titulo.value || 'Asunto del aviso');
            document.getElementById('pv-motivo').textContent = (motivo.value || 'El motivo aparece destacado.');
            var extra = document.getElementById('pv-cuerpo');
            extra.textContent = cuerpo.value || '';
            extra.classList.toggle('pv-empty', !cuerpo.value);
        }
        ['av-titulo', 'av-motivo', 'av-cuerpo'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', syncPreview);
        });
        syncPreview();
    })();
</script>
@endsection
