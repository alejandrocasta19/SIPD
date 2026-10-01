@extends('layouts.master')

@php $pageTitle = 'Avisar al equipo'; @endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Avisar al equipo</h1>
            <p>Llega como aviso de coordinación, con tu nombre y el motivo. No se mezcla con plazos o permisos.</p>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .proc-head { max-width: 760px; margin-left: auto; margin-right: auto; }
    .nt-card {
        background: #fff;
        border: 1px solid var(--cth-border);
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        max-width: 760px;
        margin: 0 auto;
        width: 100%;
    }
    .nt-card label.field {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748b;
        margin: 0 0 8px;
    }
    .nt-card input[type="text"],
    .nt-card select,
    .nt-card textarea {
        width: 100%;
        border: 1px solid var(--cth-border);
        border-radius: 10px;
        padding: 10px 12px;
        font: inherit;
        background: #fff;
    }
    .nt-card textarea { min-height: 88px; resize: vertical; }
    .nt-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .nt-fields .span-2 { grid-column: 1 / -1; }
    .who-block { margin-bottom: 18px; }
    .who-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .who-head .field { margin: 0; }
    .who-head button {
        border: 0; background: none; color: var(--cth-green);
        font-weight: 700; font-size: 12px; cursor: pointer; padding: 0;
    }
    .who-list { display: flex; flex-wrap: wrap; gap: 8px; }
    .who-chip {
        display: inline-flex; align-items: center; gap: 8px;
        margin: 0; padding: 8px 14px; border: 1px solid var(--cth-border);
        border-radius: 999px; background: #f8fafc; color: #334155;
        font-weight: 600; font-size: 13px; cursor: pointer;
    }
    .who-chip input { width: auto; margin: 0; accent-color: var(--cth-green); }
    .who-chip.is-on,
    .who-chip:has(input:checked) {
        background: #ecfdf5;
        border-color: #bbf7d0;
        color: var(--cth-green-text);
    }
    .who-empty { color: #94a3b8; font-size: 13px; }
    .nt-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
    @media (max-width: 720px) {
        .nt-fields { grid-template-columns: 1fr; }
        .nt-fields .span-2 { grid-column: auto; }
    }
</style>
@endsection

@section('content')
    <form class="nt-card" method="POST" action="{{ route('coordinadora.notificar.enviar') }}" id="formAvisoEquipo">
        @csrf
        <div class="who-block">
            <div class="who-head">
                <label class="field">Destinatarios</label>
                <button type="button" id="toggleTodos">Seleccionar todos</button>
            </div>
            <div class="who-list" id="listaDestinatarios">
                @forelse($equipo as $miembro)
                    <label class="who-chip">
                        <input type="checkbox" name="destinatarios[]" value="{{ $miembro->id }}" {{ collect(old('destinatarios'))->contains($miembro->id) ? 'checked' : '' }}>
                        {{ $miembro->name }}
                    </label>
                @empty
                    <span class="who-empty">No hay integrantes de RH.</span>
                @endforelse
            </div>
        </div>
        <div class="nt-fields">
            <div>
                <label class="field" for="av-titulo">Asunto</label>
                <input id="av-titulo" type="text" name="titulo" value="{{ old('titulo') }}" required maxlength="160" placeholder="Ej. Revisar descargos de PRO-027">
            </div>
            <div>
                <label class="field" for="av-proceso">Expediente relacionado (opcional)</label>
                <select id="av-proceso" name="proceso_id">
                    <option value="">Ninguno</option>
                    @foreach($procesos as $proceso)
                        <option value="{{ $proceso->id }}" {{ (string) old('proceso_id') === (string) $proceso->id ? 'selected' : '' }}>
                            PRO-{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }} · {{ $proceso->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="span-2">
                <label class="field" for="av-motivo">Motivo</label>
                <textarea id="av-motivo" name="motivo" rows="3" required maxlength="500" placeholder="Por qué se les avisa.">{{ old('motivo') }}</textarea>
            </div>
            <div class="span-2">
                <label class="field" for="av-cuerpo">Instrucción (opcional)</label>
                <textarea id="av-cuerpo" name="cuerpo" rows="4" maxlength="2000" placeholder="Qué deben hacer o el contexto adicional.">{{ old('cuerpo') }}</textarea>
            </div>
            <div class="span-2 nt-actions">
                <a class="btn-ghost" href="{{ route('abogado.dashboard') }}">Cancelar</a>
                <button type="submit" class="btn-ok">Enviar aviso</button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    (function () {
        var todos = document.getElementById('toggleTodos');
        var boxes = document.querySelectorAll('#listaDestinatarios input[type="checkbox"]');
        function syncChips() {
            boxes.forEach(function (b) {
                var chip = b.closest('.who-chip');
                if (chip) chip.classList.toggle('is-on', b.checked);
            });
            if (todos) {
                var algunoOff = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
                todos.textContent = algunoOff ? 'Seleccionar todos' : 'Quitar todos';
            }
        }
        if (todos) {
            todos.addEventListener('click', function () {
                var marcar = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
                boxes.forEach(function (b) { b.checked = marcar; });
                syncChips();
            });
        }
        boxes.forEach(function (b) { b.addEventListener('change', syncChips); });
        syncChips();
    })();
</script>
@endsection
