@extends('layouts.master')

@section('styles')
<style>
:root {
    --c-yellow: #fbbf24;
}

/* ─── Layout principal igual que edit.blade.php ─── */
.edit-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .edit-layout { grid-template-columns: 1fr; }
    .preview-panel { order: -1; }
}

.case-info-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
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
    border: 1px solid #e2e8f0;
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
    gap: 12px;
    background: #f8fafc;
}
.panel-head h3 {
    margin: 0; font-size: 15px; font-weight: 700;
    color: var(--c-slate); display: flex; align-items: center; gap: 8px;
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
    border: 1px solid #cbd5e1; background: #fff;
    padding: 8px 12px; border-radius: 8px;
    margin-top: 4px; cursor: pointer; font-size: 12px;
}
#worker-results button:hover { background: #f0fdf4; border-color: var(--c-green); }

.submodule-panel { display: none; }
.submodule-panel.active { display: block; }
</style>
@endsection

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Nuevo Proceso Disciplinario</h1>
            <p>Registra el expediente y diligencia los 3 documentos oficiales</p>
        </div>
    </div>
    <div class="page-banner">
        <div class="page-banner-left">
            <div class="page-banner-title">Nuevo Proceso Disciplinario</div>
            <div class="page-banner-sub">Completa los datos del trabajador para registrar el expediente &middot; {{ now()->format('d/m/Y') }}</div>
        </div>
        <div class="page-banner-right">
            <span class="pb-badge">Borrador</span>
            <span class="pb-badge green">Nuevo</span>
        </div>
    </div>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-warning" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-warning" style="margin-bottom:16px;">
        <strong>Por favor revisa la siguiente información:</strong>
        <ul style="margin:6px 0 0;padding-left:18px;">
            @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
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
    <input type="hidden" name="tipo_proceso" id="tipo_proceso" value="disciplinario">

    @php
        $me = $profileUser;
        $iniciales = collect(preg_split('/\s+/', trim($me->name)))->filter()->take(2)
            ->map(fn($p) => strtoupper(substr($p,0,1)))->implode('');
    @endphp

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
            <div class="f-grid-2">
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Nombre del Trabajador *</label>
                    <input type="text" name="nombre" id="inp-nombre" class="field-input" required placeholder="Nombre completo" value="{{ old('nombre', request('nombre')) }}">
                </div>
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Cédula</label>
                    <input type="text" name="cedula" id="inp-cedula" class="field-input" placeholder="Número de cédula" value="{{ old('cedula', request('cedula')) }}">
                </div>
            </div>
            <div class="f-grid-3" style="margin-top:14px;">
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Cargo del Trabajador</label>
                    <input type="text" name="modalidad" id="inp-cargo" class="field-input" placeholder="Ej: Conductor / Taquillero" value="{{ old('modalidad', request('modalidad')) }}">
                </div>
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Teléfono</label>
                    <input type="text" name="telefono" class="field-input" placeholder="Teléfono de contacto" value="{{ old('telefono', request('telefono')) }}">
                </div>
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Fecha de la Falta</label>
                    <input type="date" name="fecha_falta" class="field-input" value="{{ old('fecha_falta') }}">
                </div>
            </div>
            <div class="f-grid-2" style="margin-top:14px;">
                <div class="field-block" style="margin-bottom:0;">
                    <label class="field-label">Tipo de Falta</label>
                    <input type="text" name="tipo_falta" class="field-input" placeholder="Ej: Incumplimiento de horario" value="{{ old('tipo_falta') }}">
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

    {{-- PESTAÑAS DE SUBMÓDULOS --}}
    <div class="sub-tab-bar">
        <button type="button" id="tab-disciplinario" class="sub-tab-btn active" onclick="selectSubmodule('disciplinario')">
            <i class="fas fa-balance-scale"></i> 1. Apertura Disciplinaria
        </button>
        <button type="button" id="tab-comprobacion" class="sub-tab-btn" onclick="selectSubmodule('comprobacion')">
            <i class="fas fa-search"></i> 2. Apertura Comprobación
        </button>
        <button type="button" id="tab-acta" class="sub-tab-btn" onclick="selectSubmodule('acta')">
            <i class="fas fa-gavel"></i> 3. Acta Cargos y Descargos
        </button>
    </div>

    {{-- INTERACTIVE DOCUMENT VIEWS --}}
    @foreach(['disciplinario', 'comprobacion', 'acta'] as $tipoDoc)
    <div class="submodule-panel {{ $tipoDoc === 'disciplinario' ? 'active' : '' }}" id="submodule-form-{{ $tipoDoc }}" {!! $tipoDoc !== 'disciplinario' ? 'style="display:none;"' : '' !!}>
        <div class="panel-card doc-preview-wrapper" style="margin-bottom: 24px;">
            <div class="panel-head" style="background:#f8fafc;">
                <h3><i class="fas fa-file-contract" style="color:var(--c-green);"></i> Documento Interactivo Oficial</h3>
                <div style="font-size:12px; color:var(--c-muted);">Las zonas amarillas son editables</div>
            </div>
            
            {{-- Toolbar superior dentro del documento --}}
            <div style="padding: 18px 24px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #fff;">
                <div class="resp-strip" style="margin-bottom:0; flex:1; min-width: 250px;">
                    <div class="resp-ava">{{ $iniciales }}</div>
                    <div class="resp-info">
                        <b>{{ $me->name }}</b>
                        <span>{{ $me->cargo ?: ucfirst($me->role) }} · {{ now()->format('d/m/Y') }}</span>
                    </div>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <label class="btn-toolbar" style="background:#f1f5f9; color:#334155; cursor:pointer; font-weight:700;">
                            <i class="fas fa-signature"></i> Subir Firma
                            <input type="file" name="firmas_digitales[{{ $tipoDoc }}]" accept="image/png, image/jpeg" style="display:none;" onchange="previewFirma(this)">
                        </label>
                        <button type="submit" class="btn-toolbar btn-save">
                            <i class="fas fa-check-circle"></i> Registrar Caso y Generar DOCX
                        </button>
                    </div>
                </div>

            {{-- Área del documento renderizado en HTML --}}
            <div class="phpword-document-container" style="background: #e2e8f0; padding: 40px 20px; overflow-x: auto;">
                <div style="background: #ffffff; max-width: 850px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 40px; border-radius: 4px; border: 1px solid #ccc; font-family: 'Arial', sans-serif;">
                    {!! $interactiveHtml[$tipoDoc] !!}
                </div>
            </div>
        </div>
    </div>
    @endforeach

</form>

<script>
window.selectSubmodule = function(tipo) {
    document.getElementById('tipo_proceso').value = tipo;
    document.querySelectorAll('.sub-tab-btn').forEach(function(b){ b.classList.remove('active'); });
    var tab = document.getElementById('tab-' + tipo);
    if(tab) tab.classList.add('active');
    document.querySelectorAll('.submodule-panel').forEach(function(p){
        p.classList.remove('active');
        p.style.display = 'none';
    });
    var panel = document.getElementById('submodule-form-' + tipo);
    if(panel){ panel.classList.add('active'); panel.style.display = 'block'; }
};

document.addEventListener('DOMContentLoaded', function () {
    var inp_nombre = document.getElementById('inp-nombre');
    var inp_cedula = document.getElementById('inp-cedula');
    var inp_cargo  = document.getElementById('inp-cargo');
    var ciName     = document.getElementById('ci-worker-name');
    var ciSub      = document.getElementById('ci-worker-sub');
    var workerSrch = document.getElementById('worker-search');
    var workerRes  = document.getElementById('worker-results');
    var timer;

    function updateBar() {
        var name  = inp_nombre ? inp_nombre.value.trim() : '';
        var cc    = inp_cedula ? inp_cedula.value.trim() : '';
        var cargo = inp_cargo  ? inp_cargo.value.trim()  : '';
        if(ciName) ciName.textContent = name ? name + (cc ? ' · C.C. ' + cc : '') : 'Nuevo Proceso Disciplinario';
        if(ciSub)  ciSub.textContent  = name
            ? (cargo || '—') + ' · Expediente en creación · {{ now()->format("d/m/Y") }}'
            : 'Completa los datos del trabajador para registrar el expediente · {{ now()->format("d/m/Y") }}';
    }

    if(inp_nombre) inp_nombre.addEventListener('input', updateBar);
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
                                if(inp_cargo)  { inp_cargo.value  = w.modalidad||''; inp_cargo.dispatchEvent(new Event('input')); }
                                var tel = document.querySelector('[name="telefono"]');
                                if(tel) { tel.value = w.telefono||''; tel.dispatchEvent(new Event('input')); }
                                updateBar();
                                workerRes.innerHTML = '<small style="color:var(--cth-green-text);display:block;margin-top:4px;">✓ Datos autocompletados.</small>';
                            });
                            workerRes.appendChild(btn);
                        });
                    });
            }, 250);
        });
    }

    // Persistencia LocalStorage
    var formFields = document.querySelectorAll('#registro-form input:not([type="file"]):not([type="hidden"]), #registro-form textarea, #registro-form select');

    // Restaurar
    formFields.forEach(function(field) {
        if(field.name) {
            var saved = localStorage.getItem('sipd_nuevo_' + field.name);
            if(saved !== null) {
                field.value = saved;
            }
        }
    });

    updateBar();

    // Guardar al teclear
    formFields.forEach(function(field) {
        field.addEventListener('input', function() {
            if(field.name) {
                localStorage.setItem('sipd_nuevo_' + field.name, field.value);
            }
        });
    });

    // Limpiar al enviar
    document.getElementById('registro-form').addEventListener('submit', function() {
        formFields.forEach(function(field) {
            if(field.name) localStorage.removeItem('sipd_nuevo_' + field.name);
        });
    });

    selectSubmodule('disciplinario');
});

function previewFirma(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            // Find signature zone inside the CURRENT active panel
            var panel = input.closest('.submodule-panel');
            if(panel) {
                panel.querySelectorAll('.sig-zone').forEach(function(z) {
                    z.innerHTML = '<img src="' + e.target.result + '" style="max-height:80px; display:block; margin:0 auto; margin-bottom: 5px;">';
                });
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection