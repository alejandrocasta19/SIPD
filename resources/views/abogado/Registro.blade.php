@extends('layouts.master')

@php $pageTitle = 'Nuevo proceso'; @endphp

@section('styles')
<style>
:root {
    --c-yellow: #fbbf24;
}

/* ─── Layout principal igual que edit.blade.php ─── */
.edit-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) minmax(0,1fr);
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .edit-layout { grid-template-columns: 1fr; }
    .preview-panel { order: -1; }
}

.case-info-bar {
    background: #fff;
    border: 1px solid var(--cth-border);
    border-radius: var(--radius);
    padding: 16px 20px;
    color: #334155;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
.ci-main { font-size: 16px; font-weight: 700; color: var(--c-slate); }
.ci-sub  { font-size: 13px; color: var(--c-muted); margin-top: 2px; }
.ci-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.ci-badge {
    background: #f8fafc;
    border: 1px solid var(--cth-border);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.ci-badge.new-doc {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: var(--cth-green);
}

.proc-head-reg { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; }

/* ─── Sub-module tab buttons ─── */
.sub-tab-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.sub-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    font: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid var(--c-border);
    background: #fff;
    color: #475569;
    transition: all .2s;
    text-decoration: none;
}
.sub-tab-btn:hover { border-color: #cbd5e1; color: #0f172a; }
.sub-tab-btn.active {
    background: #f0fdf4;
    color: var(--cth-green-text);
    border-color: var(--c-green);
    box-shadow: 0 2px 8px rgba(34,197,94,.15);
}

/* ─── Panel card ─── */
.panel-card {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid var(--c-border);
    box-shadow: 0 2px 8px rgba(15,23,42,.06);
    overflow: hidden;
}
.panel-head {
    padding: 18px 24px;
    border-bottom: 1px solid var(--c-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: #f8fafc;
}
.panel-head h3 {
    margin: 0; font-size: 15px; font-weight: 700;
    color: var(--c-slate); display: flex; align-items: center; gap: 8px;
}
.doc-yellow-hint {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    max-width: 420px;
    padding: 8px 12px;
    border-radius: 10px;
    background: #fefce8;
    border: 1px solid #fde68a;
    color: #854d0e;
    font-size: 13px;
    line-height: 1.4;
}
.doc-yellow-hint i { margin-top: 2px; color: #ca8a04; }
.doc-yellow-hint strong { display: block; font-weight: 800; }
.doc-yellow-hint small {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #a16207;
}

/* ─── Responsable strip ─── */
.resp-strip {
    background: #f0fdf4;
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    border: 1px solid #bbf7d0;
}
.resp-ava {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--c-green-dk); color: #fff;
    display: grid; place-items: center;
    font-weight: 700; font-size: 13px; flex-shrink: 0;
}
.resp-info b   { display: block; font-size: 13px; color: #0f172a; }
.resp-info span { font-size: 11px; color: var(--cth-green-text); }

/* ─── Progress bar ─── */
.progress-label { font-size: 12px; color: var(--c-muted); margin-bottom: 6px; }
.progress-bar-wrap {
    height: 4px; background: #e2e8f0;
    border-radius: 999px; overflow: hidden; margin-bottom: 18px;
}
.progress-bar-fill {
    height: 100%; background: var(--c-green);
    border-radius: 999px; transition: width .4s;
}

/* ─── Section accordion ─── */
.section-block {
    border: 1px solid var(--c-border);
    border-radius: 12px; overflow: hidden; margin-bottom: 12px;
}
.section-toggle {
    width: 100%; background: #f8fafc; border: none;
    text-align: left; padding: 14px 18px; font: inherit;
    font-size: 14px; font-weight: 700; color: var(--c-slate);
    cursor: pointer; display: flex; align-items: center;
    justify-content: space-between; gap: 10px;
}
.section-toggle:hover { background: #f1f5f9; }
.section-toggle-arrow { transition: transform .2s; font-size: 12px; color: var(--c-muted); }
.section-toggle.open .section-toggle-arrow { transform: rotate(90deg); }
.section-desc { font-size: 12px; color: var(--c-muted); font-weight: 400; margin-top: 2px; }
.section-content { padding: 16px 18px; display: none; }
.section-content.open { display: block; }

/* ─── Fields ─── */
.field-block { margin-bottom: 16px; }
.field-block:last-child { margin-bottom: 0; }
.field-label {
    display: block; font-size: 12px; font-weight: 700;
    color: #334155; margin-bottom: 6px;
    letter-spacing: .03em; text-transform: uppercase;
}
.field-yellow-hint {
    display: inline-block; width: 10px; height: 10px;
    background: var(--c-yellow); border-radius: 2px;
    margin-right: 6px; vertical-align: middle;
}
.field-textarea, .field-input {
    width: 100%; border: 1.5px solid var(--c-border);
    border-radius: 10px; padding: 10px 12px; font: inherit;
    font-size: 13px; color: var(--c-slate);
    transition: border-color .15s, box-shadow .15s;
    resize: vertical; box-sizing: border-box;
}
.field-textarea { min-height: 90px; }
.field-textarea.large { min-height: 140px; }
.field-textarea:focus, .field-input:focus {
    outline: none; border-color: var(--c-green);
    box-shadow: 0 0 0 3px rgba(34,197,94,.1);
}

/* ─── Grid para campos del trabajador ─── */
.f-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.f-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 14px; }
@media (max-width: 700px) { .f-grid-2, .f-grid-3 { grid-template-columns: 1fr; } }
.mod-note { margin: -8px 0 16px; font-size: 13px; font-weight: 500; color: #64748b; }
.mod-step {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}
.mod-step.is-done { color: var(--cth-green-text); font-weight: 600; }
.mod-sub {
    margin: -4px 0 16px;
    padding: 14px 14px 2px;
    border-radius: 14px;
    background: #fffbeb;
    border: 1.5px dashed #f59e0b;
}
.mod-sub .mod-step { color: #92400e; }
.mod-sub.is-done {
    background: #f0fdf4;
    border-style: solid;
    border-color: #bbf7d0;
}
.js-grupo .mod-chevron { font-size: 11px; margin-left: 2px; transition: transform .2s; }
.js-grupo.is-open {
    background: #fff;
    color: var(--cth-green-text);
    border-color: var(--c-green);
}
.js-grupo.is-open .mod-chevron { transform: rotate(180deg); }
.doc-actions {
    max-width: 850px;
    margin: 16px auto 0;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}
.doc-actions .resp-strip {
    margin: 0;
    flex: 1;
    min-width: 220px;
    background: #f0fdf4;
}
.doc-actions-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
.btn-toolbar.btn-firma {
    background: #fff;
    color: #0f172a;
    border: 1.5px solid #dbe3ee;
    border-radius: 12px;
    padding: 12px 18px;
    cursor: pointer;
}
.btn-toolbar.btn-registrar {
    background: var(--c-green);
    color: #fff;
    border: 0;
    border-radius: 12px;
    padding: 12px 20px;
    box-shadow: 0 8px 18px rgba(0, 104, 55, .22);
}
label.sub-tab-btn { position: relative; margin: 0; }
label.sub-tab-btn .mod-radio {
    position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer;
}

/* ─── Toolbar ─── */
.form-toolbar {
    display: flex; gap: 10px; flex-wrap: wrap;
    padding-top: 16px; border-top: 1px solid var(--c-border); margin-top: 16px;
}
.btn-toolbar {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 18px; border-radius: 10px; font: inherit;
    font-size: 13px; font-weight: 700; border: none;
    cursor: pointer; text-decoration: none;
    transition: opacity .2s, transform .1s;
}
.btn-toolbar:hover { opacity: .88; transform: translateY(-1px); }
.btn-save    { background: var(--c-green); color: #fff; }
.btn-preview { background: #f1f5f9; color: #334155; }

/* ─── Preview panel ─── */
.preview-panel { position: sticky; top: 20px; }
.preview-frame-wrap {
    border-radius: 0 0 var(--radius) var(--radius);
    overflow: hidden; position: relative;
}
.preview-placeholder {
    height: 580px; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    color: var(--c-muted); gap: 12px; background: #f8fafc;
}
.preview-placeholder i { font-size: 48px; opacity: .3; }
.preview-placeholder p { margin: 0; font-size: 14px; text-align: center; }
.preview-overlay {
    display: none; position: absolute; inset: 0;
    background: rgba(255,255,255,.8); place-items: center;
    z-index: 10; backdrop-filter: blur(2px);
}
.preview-overlay.active { display: grid; }
.preview-spinner { font-size: 28px; color: var(--c-green); }

/* ─── Worker search autocomplete ─── */
#worker-results button {
    display: block; width: 100%; text-align: left;
    border: 1px solid var(--cth-border); background: #fff;
    padding: 8px 12px; border-radius: 8px;
    margin-top: 4px; cursor: pointer; font-size: 12px;
}
#worker-results button:hover { background: #f0fdf4; border-color: var(--c-green); }

.submodule-panel { display: none; }
.submodule-panel.active { display: block; }

.phpword-document-container { overflow-x: auto; }
#doc-html-host { overflow-x: hidden; max-width: 850px; }
#doc-html-host p { margin: 0.28em 0; }
#doc-html-host img { max-width: 100%; height: auto; max-height: 90px; }
#doc-html-host table { width: 100% !important; height: auto !important; }
#doc-html-host td, #doc-html-host th { height: auto !important; }
#doc-html-host textarea.doc-interactive-field { max-width: 100%; box-sizing: border-box; }
#doc-html-host textarea.js-inline { height: 2em !important; min-height: 2em !important; overflow: hidden !important; vertical-align: baseline; white-space: nowrap; }
#doc-html-host .doc-header-field { font-weight: 700; border-bottom: 1px solid #0f172a; padding: 0 4px; }
#doc-html-host .doc-optional-clause,
#doc-html-host .doc-optional-bar,
#doc-html-host .doc-optional-bar input,
#doc-html-host .doc-optional-bar label { pointer-events: auto; }
#doc-html-host .doc-optional-bar label { cursor: pointer; }
</style>
@endsection

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Nuevo proceso disciplinario</h1>
            <p>Completa los datos del trabajador para registrar el expediente · {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>
@endsection

@section('content')

@if($errors->any())
    <div class="sipd-alert sipd-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Revisa la siguiente información</strong>
            <ul>
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- BARRA OSCURA DINÁMICA (igual que edit.blade.php) --}}
<div class="case-info-bar">
    <div>
        <div class="ci-main" id="ci-worker-name">Nuevo Proceso Disciplinario</div>
        <div class="ci-sub" id="ci-worker-sub">Completa los datos del trabajador para registrar el expediente · {{ now()->format('d/m/Y') }}</div>
    </div>
    <div class="ci-badges">
        <span class="ci-badge">Borrador</span>
        <span class="ci-badge new-doc">Nuevo</span>
    </div>
</div>

<form action="{{ route('abogado.registro.store') }}" method="POST" enctype="multipart/form-data" id="registro-form">
    @csrf
    @php
        $me = $profileUser;
        $iniciales = collect(preg_split('/\s+/', trim($me->name)))->filter()->take(2)
            ->map(fn($p) => strtoupper(substr($p,0,1)))->implode('');
        $slotsUi = [
            'apertura' => ['icon' => 'fa-balance-scale', 'label' => '1. Apertura'],
            'acta' => ['icon' => 'fa-gavel', 'label' => '2. Acta de cargos y descargos'],
            'resolucion' => ['icon' => 'fa-stamp', 'label' => '3. Sanción / llamado / terminación'],
            'archivo' => ['icon' => 'fa-archive', 'label' => '4. Decisión de archivo'],
        ];
        $slotInicial = \App\Models\CasoDocumentoEstado::slotDe($tipoInicial) ?: 'apertura';
    @endphp
    <input type="hidden" name="tipo_proceso" id="tipo_proceso" value="{{ $tipoInicial }}">

    @php
        $grupoAbierto = null;
        foreach ($selector as $item) {
            if (in_array($modalidadSeleccionada, $item['opciones'], true)) {
                $grupoAbierto = $item['id'];
            }
        }
        $mostrarExpediente = $modalidadSeleccionada !== '' && \App\Support\Modalidades::interpretar($modalidadSeleccionada, old('cargo')) !== null;
    @endphp

    @php
        $nombreGrupoAbierto = '';
        foreach ($selector as $item) {
            if ($grupoAbierto === $item['id']) {
                $nombreGrupoAbierto = $item['nombre'];
            }
        }
        if ($mostrarExpediente) {
            $textoModalidad = 'Modalidad seleccionada: ' . $modalidadSeleccionada . '.';
        } elseif ($nombreGrupoAbierto !== '') {
            $textoModalidad = 'Elige una opción de ' . $nombreGrupoAbierto . ' para continuar.';
        } else {
            $textoModalidad = 'Selecciona una modalidad para continuar.';
        }
    @endphp
    <p class="mod-step{{ $mostrarExpediente ? ' is-done' : '' }}" id="mod-note">{{ $textoModalidad }}</p>
    <div class="sub-tab-bar" id="modalidad-grid">
        @foreach($selector as $item)
            @if($item['tipo'] === 'grupo')
                <button type="button" class="sub-tab-btn js-grupo{{ $grupoAbierto === $item['id'] ? ' is-open' : '' }}" data-grupo="{{ $item['id'] }}" data-nombre="{{ $item['nombre'] }}">
                    {{ $item['nombre'] }}
                    <i class="fas fa-chevron-down mod-chevron" aria-hidden="true"></i>
                </button>
            @else
                <label class="sub-tab-btn{{ $modalidadSeleccionada === $item['nombre'] ? ' active' : '' }}">
                    <input class="mod-radio" type="radio" name="modalidad" value="{{ $item['nombre'] }}" {{ $modalidadSeleccionada === $item['nombre'] ? 'checked' : '' }}>
                    {{ $item['nombre'] }}
                </label>
            @endif
        @endforeach
    </div>
    @foreach($selector as $item)
        @if($item['opciones'] !== [])
            @php $subElegida = in_array($modalidadSeleccionada, $item['opciones'], true); @endphp
            <div class="mod-sub js-sub{{ $subElegida ? ' is-done' : '' }}" id="sub-{{ $item['id'] }}" @unless($grupoAbierto === $item['id']) hidden @endunless>
                <p class="mod-step js-sub-hint" @if($subElegida) hidden @endif>Elige una opción. El formulario aparece al seleccionarla.</p>
                <div class="sub-tab-bar">
                    @foreach($item['opciones'] as $opcion)
                        <label class="sub-tab-btn{{ $modalidadSeleccionada === $opcion ? ' active' : '' }}">
                            <input class="mod-radio" type="radio" name="modalidad" value="{{ $opcion }}" {{ $modalidadSeleccionada === $opcion ? 'checked' : '' }}>
                            {{ $opcion }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <div id="expediente-rest" {{ $mostrarExpediente ? '' : 'hidden' }}>
    <div class="panel-card" style="margin-bottom: 24px;">
        <div class="panel-head">
            <h3><i class="fas fa-user-circle" style="color:var(--c-green);"></i> Datos Generales del Expediente</h3>
        </div>
        <div class="panel-body" style="padding: 24px;">
            <div class="field-block">
                <label class="field-label">Buscar trabajador existente</label>
                <input type="search" id="worker-search" class="field-input" autocomplete="off" placeholder="Escriba nombre o cédula para autocompletar...">
                <div id="worker-results"></div>
            </div>
            @php
                $placaAplica = \App\Support\Modalidades::usaPlaca($modalidadSeleccionada);
                $cargoAplica = in_array($modalidadSeleccionada, \App\Support\Modalidades::opcionesConCampoCargo(), true);
            @endphp
            <div class="f-grid-2">
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Nombre del Trabajador *</label>
                    <input type="text" name="nombre" id="inp-nombre" class="field-input" required title="Solo letras" placeholder="Solo letras" value="{{ old('nombre', request('nombre')) }}">
                </div>
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Cédula *</label>
                    <input type="text" name="cedula" id="inp-cedula" class="field-input" required inputmode="numeric" pattern="[0-9]+" title="Solo números" placeholder="Solo números" value="{{ old('cedula', request('cedula')) }}">
                </div>
            </div>
            <div class="field-block" id="placa-field" style="margin-top:14px;"{{ $placaAplica ? '' : ' hidden' }}>
                <label class="field-label">Placa del vehículo</label>
                <input type="text" name="placa" id="inp-placa" class="field-input" placeholder="Ej: GRK206" value="{{ old('placa', request('placa')) }}"{{ $placaAplica ? '' : ' disabled' }}>
            </div>
            <div class="field-block" id="cargo-field" style="margin-top:14px;"{{ $cargoAplica ? '' : ' hidden' }}>
                <label class="field-label">Cargo</label>
                <input type="text" name="cargo" id="inp-cargo" class="field-input" placeholder="Cargo del trabajador" value="{{ old('cargo', request('cargo')) }}"{{ $cargoAplica ? '' : ' disabled' }}>
            </div>
            <div class="f-grid-2" style="margin-top:14px;">
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Teléfono</label>
                    <input type="text" name="telefono" id="inp-telefono" class="field-input" inputmode="numeric" pattern="[0-9]*" title="Solo números" placeholder="Solo números" value="{{ old('telefono', request('telefono')) }}">
                </div>
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Fecha de la Falta</label>
                    <input type="date" name="fecha_falta" class="field-input" value="{{ old('fecha_falta') }}">
                </div>
            </div>
            <div class="f-grid-2" style="margin-top:14px;">
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Tipo de Falta</label>
                    <input type="text" name="tipo_falta" class="field-input" placeholder="Ej: Abandono de ruta, Retraso..." value="{{ old('tipo_falta') }}" autocomplete="off">
                </div>
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Adjuntar Evidencia (PDF, JPG, PNG)</label>
                    <input type="file" name="documento_falta[]" class="field-input" accept=".pdf,.jpg,.jpeg,.png" multiple>
                </div>
            </div>
            <div class="field-block" style="margin-top:14px;">
                <label class="field-label">Descripción de la Falta</label>
                <textarea name="descripcion_falta" class="field-textarea" rows="2" placeholder="Detalle la falta cometida...">{{ old('descripcion_falta') }}</textarea>
            </div>
        </div>
    </div>

    {{-- PESTAÑAS DE DOCUMENTOS --}}
    <div class="sub-tab-bar" id="etapa-grid">
        @foreach($slotsUi as $slot => $meta)
            <button type="button" id="tab-{{ $slot }}" class="sub-tab-btn" onclick="selectSlot('{{ $slot }}')">
                <i class="fas {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
            </button>
        @endforeach
    </div>
    <p class="mod-note" id="etapa-note">Elige un paso. El formato y el documento aparecen al seleccionarlo.</p>

    <div id="etapa-rest" hidden>
    <div class="panel-card doc-preview-wrapper" style="margin-bottom: 24px;">
        <div class="panel-head" style="background:#f8fafc;">
            <h3><i class="fas fa-file-contract" style="color:var(--c-green);"></i> Documento interactivo oficial</h3>
            <div class="doc-yellow-hint">
                <i class="fas fa-highlighter"></i>
                <div>
                    <strong>Las zonas amarillas son editables.</strong>
                    Las que no se rellenen no se generan en el documento.
                    <small>Al registrar queda en borrador; genera y descarga en Generar documentos.</small>
                </div>
            </div>
        </div>

        <div id="variant-bar" style="padding:14px 24px; border-bottom:1px solid var(--c-border); display:none; gap:8px; flex-wrap:wrap; align-items:center; background:#fff;">
            <span style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Usar formato</span>
            <div id="variant-options" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
        </div>

        <div id="terminacion-hint" class="sipd-alert sipd-alert-warning" style="display:none;margin:16px 24px 0;">
            <i class="fas fa-print"></i>
            <div>Este formato se llena y queda registrado igual que los demás. Después hay que imprimirlo para firma del gerente y subir el escaneo en Anexos escaneados.</div>
        </div>

        <div class="phpword-document-container" style="background: #e2e8f0; padding: 24px 16px 28px; overflow-x: auto;">
            <div id="doc-html-host" style="background: #ffffff; max-width: 850px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 28px 32px; border-radius: 4px; border: 1px solid #ccc; font-family: 'Arial', sans-serif;">
                {!! $interactiveHtml !!}
            </div>
            <div class="doc-actions">
                <div class="resp-strip">
                    <div class="resp-ava">{{ $iniciales }}</div>
                    <div class="resp-info">
                        <b>{{ $me->name }}</b>
                        <span>{{ $me->cargo ?: ucfirst($me->role) }} · {{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>
                <div class="doc-actions-btns">
                    <label id="firma-cuenta-control" class="btn-toolbar btn-firma">
                        <i class="fas fa-signature"></i> <span id="firma-cuenta-label">Subir mi firma</span>
                        <input type="file" name="firma_cuenta" id="firma-registro" accept="image/png, image/jpeg" style="display:none;" onchange="mostrarFirmaSeleccionada(this, 'firma-cuenta-label')">
                    </label>
                    <label id="firma-trabajador-control" class="btn-toolbar btn-firma">
                        <i class="fas fa-signature"></i> <span id="firma-trabajador-label">Firma del trabajador</span>
                        <input type="file" name="firma_trabajador" id="firma-trabajador-registro" accept="image/png, image/jpeg" style="display:none;" onchange="mostrarFirmaSeleccionada(this, 'firma-trabajador-label')">
                    </label>
                    <button type="submit" class="btn-toolbar btn-registrar">
                        <i class="fas fa-save"></i> Registrar proceso
                    </button>
                </div>
                <div id="firma-politica-nota" style="display:none;width:100%;font-size:12px;color:#475569;"></div>
            </div>
        </div>
    </div>
    </div>
    </div>

</form>

<script>
var SLOT_VARIANTES = @json(\App\Models\CasoDocumentoEstado::SLOTS);
var SLOT_ETIQUETAS = @json(\App\Models\CasoDocumentoEstado::ETIQUETAS);
var PLANTILLA_URL = @json(route('abogado.registro.plantilla'));
var USER_IS_COORDINATOR = @json(auth()->user()->esCoordinadora());
var TIPO_SERVIDOR = @json($tipoInicial);
var hadValidationError = @json($errors->any());
var currentSlot = @json($slotInicial);
var currentTipo = @json($tipoInicial);
var etapaAbierta = false;
var plantillaCache = {};
var MODALIDADES_PLACA = @json(\App\Support\Modalidades::conPlaca());
var MODALIDADES_CAMPO_CARGO = @json(\App\Support\Modalidades::opcionesConCampoCargo());
var DRAFT_KEY = 'sipd_nuevo_proceso';
var persistTimer;

function emptyDraft() {
    return { slot: 'apertura', tipo: 'disciplinario', fields: {}, yellow: {}, optional: {}, etapaAbierta: false };
}

function readDraft() {
    try {
        var raw = localStorage.getItem(DRAFT_KEY);
        if (!raw) return emptyDraft();
        var d = JSON.parse(raw);
        if (!d || typeof d !== 'object') return emptyDraft();
        d.fields = d.fields && typeof d.fields === 'object' ? d.fields : {};
        d.yellow = d.yellow && typeof d.yellow === 'object' ? d.yellow : {};
        d.optional = d.optional && typeof d.optional === 'object' ? d.optional : {};
        d.etapaAbierta = !!d.etapaAbierta;
        return d;
    } catch (e) {
        return emptyDraft();
    }
}

function writeDraft(d) {
    try { localStorage.setItem(DRAFT_KEY, JSON.stringify(d)); } catch (e) {}
}

function slotOf(tipo) {
    var slots = SLOT_VARIANTES || {};
    for (var s in slots) {
        if (Object.prototype.hasOwnProperty.call(slots, s) && slots[s].indexOf(tipo) !== -1) {
            return s;
        }
    }
    return 'apertura';
}

function isValidTipo(tipo) {
    return !!(SLOT_ETIQUETAS && SLOT_ETIQUETAS[tipo]);
}

function generalFieldNodes() {
    return document.querySelectorAll('#registro-form input:not([type="file"]):not([type="hidden"]), #registro-form textarea, #registro-form select');
}

function isYellowField(el) {
    return !!(el && el.name && el.name.indexOf('yellow_blocks_') === 0);
}

function modalidadActual() {
    var sel = document.querySelector('#registro-form input[name="modalidad"]:checked');
    return sel ? sel.value.trim() : '';
}

function syncModalidadVista() {
    var seleccion = modalidadActual();
    document.querySelectorAll('#modalidad-grid .sub-tab-btn, .js-sub .sub-tab-btn').forEach(function(btn) {
        if (btn.classList.contains('js-grupo')) return;
        var input = btn.querySelector('input');
        btn.classList.toggle('active', !!(input && input.checked));
    });
    var grupoAbierto = '';
    document.querySelectorAll('.js-grupo').forEach(function(btn) {
        var panel = document.getElementById('sub-' + btn.getAttribute('data-grupo'));
        var elegido = !!(panel && panel.querySelector('input:checked'));
        if (panel && elegido) panel.hidden = false;
        var abierto = !!(panel && !panel.hidden);
        btn.classList.toggle('is-open', abierto);
        btn.classList.remove('active');
        if (panel) {
            panel.classList.toggle('is-done', elegido);
            var hint = panel.querySelector('.js-sub-hint');
            if (hint) hint.hidden = elegido;
        }
        if (abierto) grupoAbierto = btn.getAttribute('data-nombre') || '';
    });
    var rest = document.getElementById('expediente-rest');
    if (rest) rest.hidden = seleccion === '';
    var note = document.getElementById('mod-note');
    if (note) {
        note.classList.toggle('is-done', seleccion !== '');
        if (seleccion) note.textContent = 'Modalidad seleccionada: ' + seleccion + '.';
        else if (grupoAbierto) note.textContent = 'Elige una opción de ' + grupoAbierto + ' para continuar.';
        else note.textContent = 'Selecciona una modalidad para continuar.';
    }
    syncPlacaVista();
    syncCargoVista();
}

function syncCargoVista() {
    var actual = modalidadActual();
    var aplica = MODALIDADES_CAMPO_CARGO.indexOf(actual) !== -1;
    var box = document.getElementById('cargo-field');
    var input = document.getElementById('inp-cargo');
    if (box) box.hidden = !aplica;
    if (input) {
        input.disabled = !aplica;
        input.required = aplica;
    }
}

function syncPlacaVista() {
    var aplica = MODALIDADES_PLACA.indexOf(modalidadActual()) !== -1;
    var box = document.getElementById('placa-field');
    var input = document.getElementById('inp-placa');
    if (box) box.hidden = !aplica;
    if (input) {
        input.disabled = !aplica;
        input.required = aplica;
    }
}

function marcarModalidad(valor) {
    var radios = document.querySelectorAll('#registro-form input[name="modalidad"]');
    var destino = null;
    radios.forEach(function(el) {
        if (el.value === valor && !el.disabled) destino = el;
    });
    if (!destino) return false;
    radios.forEach(function(el) { el.checked = el === destino; });
    syncModalidadVista();
    return true;
}

function collectGeneralFields() {
    var fields = {};
    generalFieldNodes().forEach(function(el) {
        if (!el.name || isYellowField(el) || el.id === 'worker-search') return;
        if (el.type === 'radio') {
            if (el.checked) fields[el.name] = el.value;
            else if (!Object.prototype.hasOwnProperty.call(fields, el.name)) fields[el.name] = '';
            return;
        }
        fields[el.name] = el.value;
    });
    return fields;
}

function applyGeneralFields(fields) {
    if (!fields) return;
    generalFieldNodes().forEach(function(el) {
        if (!el.name || isYellowField(el) || !Object.prototype.hasOwnProperty.call(fields, el.name)) return;
        if (el.type === 'radio') {
            el.checked = !el.disabled && el.value === fields[el.name];
            return;
        }
        el.value = fields[el.name];
    });
}

function yellowIndex(el) {
    if (!el || !el.name) return null;
    var m = el.name.match(/\[(\d+)\]/);
    return m ? m[1] : null;
}

function collectYellow() {
    var values = {};
    document.querySelectorAll('#doc-html-host textarea[name^="yellow_blocks_"], #doc-html-host input[name^="yellow_blocks_"]').forEach(function(el) {
        var idx = yellowIndex(el);
        if (idx !== null) values[idx] = el.value;
    });
    return values;
}

function collectOptional() {
    var hidden = document.querySelector('#doc-html-host .js-optional-value');
    return hidden ? hidden.value : 'omit';
}

function applyOptional(value) {
    var hidden = document.querySelector('#doc-html-host .js-optional-value');
    if (hidden && value) hidden.value = value;
}

function applyYellow(values) {
    if (!values) return;
    document.querySelectorAll('#doc-html-host textarea[name^="yellow_blocks_"], #doc-html-host input[name^="yellow_blocks_"]').forEach(function(el) {
        var idx = yellowIndex(el);
        if (idx !== null && Object.prototype.hasOwnProperty.call(values, idx)) {
            el.value = values[idx];
        }
    });
}

function hostMatchesTipo(tipo) {
    return !!document.querySelector('#doc-html-host [name^="yellow_blocks_' + tipo + '"]');
}

function persistDraft() {
    var d = readDraft();
    d.slot = currentSlot;
    d.tipo = currentTipo;
    d.etapaAbierta = !!etapaAbierta;
    d.fields = Object.assign({}, d.fields || {}, collectGeneralFields());
    d.yellow = d.yellow || {};
    d.optional = d.optional || {};
    if (hostMatchesTipo(currentTipo)) {
        d.yellow[currentTipo] = collectYellow();
        d.optional[currentTipo] = collectOptional();
    }
    writeDraft(d);
}

function persistDraftSoon() {
    clearTimeout(persistTimer);
    persistTimer = setTimeout(persistDraft, 150);
}

function migrateLegacyKeys(d) {
    ['nombre', 'cedula', 'modalidad', 'telefono', 'fecha_falta', 'tipo_falta', 'descripcion_falta'].forEach(function(name) {
        try {
            var old = localStorage.getItem('sipd_nuevo_' + name);
            if (old !== null && (d.fields[name] == null || d.fields[name] === '')) {
                d.fields[name] = old;
            }
            localStorage.removeItem('sipd_nuevo_' + name);
        } catch (e) {}
    });
}

function bindYellowPersist(root) {
    (root || document).querySelectorAll('#doc-html-host textarea[name^="yellow_blocks_"], #doc-html-host input[name^="yellow_blocks_"]').forEach(function(el) {
        if (el.dataset.draftBound) return;
        el.dataset.draftBound = '1';
        el.addEventListener('input', persistDraftSoon);
    });
}

function bindOptionalPersist(root) {
    (root || document).querySelectorAll('#doc-html-host .doc-optional-clause').forEach(function(box) {
        if (box.dataset.draftBound) return;
        box.dataset.draftBound = '1';
        box.addEventListener('change', persistDraftSoon);
    });
}

function hydratePlantilla(tipo) {
    var host = document.getElementById('doc-html-host');
    applyYellow((readDraft().yellow || {})[tipo]);
    applyOptional((readDraft().optional || {})[tipo]);
    if (typeof window.initSipdOptionalClauses === 'function') {
        window.initSipdOptionalClauses(host);
    }
    autosizeTextareas(host);
    bindYellowPersist(host);
    bindOptionalPersist(host);
    syncDocHeader();
    persistDraft();
}

function syncDocHeader() {
    var map = {
        nombre: ((document.getElementById('inp-nombre') || {}).value || '').trim(),
        cargo: ((document.getElementById('inp-cargo') || {}).value || '').trim(),
        cedula: ((document.getElementById('inp-cedula') || {}).value || '').trim()
    };
    document.querySelectorAll('#doc-html-host [data-header]').forEach(function(el) {
        var key = el.getAttribute('data-header');
        if (!Object.prototype.hasOwnProperty.call(map, key)) return;
        var blank = el.getAttribute('data-blank') || '';
        el.textContent = map[key] || blank;
    });
}

function setActiveTab(slot) {
    document.querySelectorAll('#etapa-grid .sub-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    var tab = document.getElementById('tab-' + slot);
    if (tab) tab.classList.add('active');
}

function syncEtapaVista() {
    var rest = document.getElementById('etapa-rest');
    var note = document.getElementById('etapa-note');
    if (rest) rest.hidden = !etapaAbierta;
    if (note) note.hidden = !!etapaAbierta;
    if (!etapaAbierta) {
        document.querySelectorAll('#etapa-grid .sub-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    }
}

function autosizeTextareas(root) {
    (root || document).querySelectorAll('textarea.js-autosize, textarea[name^="yellow_blocks_"]').forEach(function(el) {
        el.removeAttribute('maxlength');
        if (el.classList.contains('js-inline')) {
            var fit = function() {
                var text = (el.value || el.placeholder || '').length;
                el.style.width = Math.max(10, text + 4) + 'ch';
            };
            el.addEventListener('input', fit);
            fit();
            return;
        }
        el.style.display = 'block';
        el.style.width = '100%';
        el.style.overflow = 'hidden';
        el.style.resize = 'vertical';
        var grow = function() {
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 34) + 'px';
        };
        el.addEventListener('input', grow);
        grow();
    });
}

function renderVariantBar(slot) {
    var bar = document.getElementById('variant-bar');
    var host = document.getElementById('variant-options');
    var variantes = SLOT_VARIANTES[slot] || [];
    if (variantes.length < 2) {
        bar.style.display = 'none';
        host.innerHTML = '';
        return;
    }
    bar.style.display = 'flex';
    host.innerHTML = '';
    variantes.forEach(function(tipo) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-toolbar' + (tipo === currentTipo ? ' btn-save' : '');
        btn.style.background = tipo === currentTipo ? '' : '#f1f5f9';
        btn.style.color = tipo === currentTipo ? '' : '#334155';
        btn.textContent = SLOT_ETIQUETAS[tipo] || tipo;
        btn.addEventListener('click', function() { loadPlantilla(tipo, slot); });
        host.appendChild(btn);
    });
}

function syncTipoHidden(tipo) {
    currentTipo = tipo;
    document.getElementById('tipo_proceso').value = tipo;
    var hint = document.getElementById('terminacion-hint');
    if (hint) hint.style.display = tipo === 'terminacion' ? 'flex' : 'none';
    var cuentaControl = document.getElementById('firma-cuenta-control');
    var trabajadorControl = document.getElementById('firma-trabajador-control');
    var cuentaInput = document.getElementById('firma-registro');
    var trabajadorInput = document.getElementById('firma-trabajador-registro');
    var firmaCoordinadora = ['sancion', 'llamado', 'archivo'].indexOf(tipo) !== -1;
    var permiteCuenta = ['comprobacion', 'disciplinario', 'acta'].indexOf(tipo) !== -1
        || (firmaCoordinadora && USER_IS_COORDINATOR);
    cuentaControl.style.display = permiteCuenta ? 'inline-flex' : 'none';
    trabajadorControl.style.display = tipo === 'acta' ? 'inline-flex' : 'none';
    cuentaInput.disabled = !permiteCuenta;
    trabajadorInput.disabled = tipo !== 'acta';
    document.getElementById('firma-cuenta-label').textContent = firmaCoordinadora
        ? 'Firma de la coordinadora'
        : 'Subir mi firma (RH)';
    var notaFirma = document.getElementById('firma-politica-nota');
    if (tipo === 'terminacion') {
        notaFirma.textContent = 'La terminación no admite firma virtual: imprímela para firma del gerente y sube el escaneo en Anexos.';
        notaFirma.style.display = 'block';
    } else if (firmaCoordinadora) {
        notaFirma.textContent = USER_IS_COORDINATOR
            ? 'Solo la coordinadora puede cargar y aplicar su firma virtual en este formato.'
            : 'La firma virtual de este formato solo puede cargarla y aplicarla la coordinadora.';
        notaFirma.style.display = 'block';
    } else {
        notaFirma.textContent = '';
        notaFirma.style.display = 'none';
    }
}

function loadPlantilla(tipo, slot) {
    persistDraft();
    if (slot) currentSlot = slot;
    syncTipoHidden(tipo);
    renderVariantBar(currentSlot);
    var host = document.getElementById('doc-html-host');
    if (plantillaCache[tipo] && plantillaCache[tipo] !== true) {
        host.innerHTML = plantillaCache[tipo];
        hydratePlantilla(tipo);
        return;
    }
    host.innerHTML = '<p style="color:#64748b;text-align:center;padding:40px 0;">Cargando formato…</p>';
    fetch(PLANTILLA_URL + '?tipo=' + encodeURIComponent(tipo), { headers: { Accept: 'application/json' }, cache: 'no-store' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            plantillaCache[tipo] = data.html || '';
            if (currentTipo !== tipo) return;
            host.innerHTML = plantillaCache[tipo];
            hydratePlantilla(tipo);
        })
        .catch(function() {
            host.innerHTML = '<p style="color:#b91c1c;text-align:center;padding:40px 0;">No se pudo cargar el formato.</p>';
        });
}

window.selectSlot = function(slot) {
    etapaAbierta = true;
    persistDraft();
    currentSlot = slot;
    setActiveTab(slot);
    syncEtapaVista();
    var variantes = SLOT_VARIANTES[slot] || [];
    var tipo = variantes.indexOf(currentTipo) !== -1 ? currentTipo : variantes[0];
    loadPlantilla(tipo, slot);
};

document.addEventListener('DOMContentLoaded', function () {
    var inp_nombre = document.getElementById('inp-nombre');
    var inp_cedula = document.getElementById('inp-cedula');
    function soloLetras(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            var limpio = this.value.replace(/[^\p{L} ']/gu, '');
            if (limpio !== this.value) this.value = limpio;
        });
    }
    function soloNumeros(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            var limpio = this.value.replace(/\D/g, '');
            if (limpio !== this.value) this.value = limpio;
        });
    }
    soloLetras(inp_nombre);
    soloLetras(document.getElementById('inp-cargo'));
    soloNumeros(inp_cedula);
    soloNumeros(document.getElementById('inp-telefono'));
    var inpPlaca = document.getElementById('inp-placa');
    if (inpPlaca) {
        inpPlaca.addEventListener('input', function () {
            var crudo = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
            var letras = crudo.slice(0, 3).replace(/[^A-Z]/g, '');
            var numeros = crudo.slice(letras.length).replace(/\D/g, '').slice(0, 3);
            this.value = letras + (numeros ? '-' + numeros : '');
        });
    }
    var ciName     = document.getElementById('ci-worker-name');
    var ciSub      = document.getElementById('ci-worker-sub');
    var workerSrch = document.getElementById('worker-search');
    var workerRes  = document.getElementById('worker-results');
    var timer;

    function updateBar() {
        var name  = inp_nombre ? inp_nombre.value.trim() : '';
        var cc    = inp_cedula ? inp_cedula.value.trim() : '';
        var cargo = modalidadActual();
        if(ciName) ciName.textContent = name ? name + (cc ? ' · C.C. ' + cc : '') : 'Nuevo Proceso Disciplinario';
        if(ciSub)  ciSub.textContent  = name
            ? (cargo || '—') + ' · Expediente en creación · {{ now()->format("d/m/Y") }}'
            : 'Completa los datos del trabajador para registrar el expediente · {{ now()->format("d/m/Y") }}';
    }

    if(inp_nombre) inp_nombre.addEventListener('input', function() { updateBar(); syncDocHeader(); });
    if(inp_cedula) inp_cedula.addEventListener('input', function() { updateBar(); syncDocHeader(); });
    document.querySelectorAll('#registro-form input[name="modalidad"]').forEach(function(el) {
        el.addEventListener('change', function() {
            if (this.checked && this.closest('#modalidad-grid')) {
                document.querySelectorAll('.js-sub input').forEach(function(input) { input.checked = false; });
                document.querySelectorAll('.js-sub').forEach(function(panel) { panel.hidden = true; });
            }
            if (this.checked && this.closest('.js-sub')) {
                document.querySelectorAll('#modalidad-grid input').forEach(function(input) { input.checked = false; });
            }
            syncModalidadVista();
            updateBar();
            syncDocHeader();
        });
    });
    document.querySelectorAll('.js-grupo').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-grupo');
            document.querySelectorAll('.js-grupo').forEach(function(otro) { otro.classList.toggle('is-open', otro === btn); });
            document.querySelectorAll('#modalidad-grid input, .js-sub input').forEach(function(input) { input.checked = false; });
            document.querySelectorAll('.js-sub').forEach(function(panel) {
                panel.hidden = panel.id !== 'sub-' + id;
            });
            syncModalidadVista();
            updateBar();
            syncDocHeader();
        });
    });
    if(workerSrch) {
        workerSrch.addEventListener('input', function() {
            clearTimeout(timer);
            var q = this.value.trim();
            if(q.length < 2){ workerRes.innerHTML = ''; return; }
            timer = setTimeout(function(){
                fetch(@json(route('abogado.trabajadores')) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                    .then(function(r){ return r.json(); })
                    .then(function(data){
                        workerRes.innerHTML = '';
                        data.forEach(function(w){
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.textContent = (w.nombre||'') + (w.cedula?' · C.C.'+w.cedula:'') + (w.modalidad?' · '+w.modalidad:'');
                            btn.addEventListener('click', function(){
                                if(inp_nombre) { inp_nombre.value = w.nombre||''; inp_nombre.dispatchEvent(new Event('input')); }
                                if(inp_cedula) { inp_cedula.value = w.cedula||''; inp_cedula.dispatchEvent(new Event('input')); }
                                marcarModalidad(w.modalidad || '');
                                var placa = document.getElementById('inp-placa');
                                if (placa && !placa.disabled) { placa.value = w.placa || ''; placa.dispatchEvent(new Event('input')); }
                                var tel = document.querySelector('[name="telefono"]');
                                if(tel) { tel.value = w.telefono||''; tel.dispatchEvent(new Event('input')); }
                                updateBar();
                                syncDocHeader();
                                workerRes.innerHTML = '<small style="color:var(--cth-green-text);display:block;margin-top:4px;">✓ Datos autocompletados.</small>';
                            });
                            workerRes.appendChild(btn);
                        });
                    });
            }, 250);
        });
    }

    var draft = readDraft();
    migrateLegacyKeys(draft);
    writeDraft(draft);

    if (!hadValidationError) {
        applyGeneralFields(draft.fields);
        if (isValidTipo(draft.tipo)) {
            currentTipo = draft.tipo;
            currentSlot = (draft.slot && SLOT_VARIANTES[draft.slot]) ? draft.slot : slotOf(draft.tipo);
        }
    }

    etapaAbierta = hadValidationError || !!draft.etapaAbierta;

    updateBar();
    syncModalidadVista();
    syncTipoHidden(currentTipo);
    syncEtapaVista();

    var hostInicial = document.getElementById('doc-html-host');
    plantillaCache[TIPO_SERVIDOR] = hostInicial ? hostInicial.innerHTML : '';

    if (etapaAbierta) {
        setActiveTab(currentSlot);
        if (currentTipo === TIPO_SERVIDOR) {
            hydratePlantilla(currentTipo);
            renderVariantBar(currentSlot);
        } else {
            loadPlantilla(currentTipo, currentSlot);
        }
    }

    generalFieldNodes().forEach(function(field) {
        if (field.name && !isYellowField(field) && field.id !== 'worker-search') {
            field.addEventListener('input', persistDraftSoon);
            field.addEventListener('change', persistDraftSoon);
        }
    });

    window.addEventListener('pagehide', persistDraft);
    window.addEventListener('beforeunload', persistDraft);

    document.getElementById('registro-form').addEventListener('submit', function() {
        persistDraft();
        var tipoActivo = document.getElementById('tipo_proceso').value;
        document.querySelectorAll('#doc-html-host textarea, #doc-html-host input').forEach(function(el) {
            if (el.name && el.name.indexOf('yellow_blocks_') === 0 && el.name.indexOf('yellow_blocks_' + tipoActivo) !== 0) {
                el.disabled = true;
            }
        });
    });
});

function mostrarFirmaSeleccionada(input, labelId) {
    if (input.files && input.files[0]) {
        var label = document.getElementById(labelId);
        label.textContent = input.files[0].name;
    }
}
</script>
@endsection