@extends('layouts.master')

@section('title', $pageTitle)

@section('styles')
<style>
:root {
    --c-yellow: #fbbf24;
    --radius: 16px;
}
.case-info-bar { background: linear-gradient(135deg, var(--cth-sidebar), var(--cth-green)); border-radius: var(--radius); padding: 20px 24px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.ci-main { font-size: 16px; font-weight: 700; }
.ci-sub  { font-size: 13px; color: rgba(255,255,255,.65); margin-top: 2px; }
.ci-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.ci-badge { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 999px; padding: 4px 12px; font-size: 12px; color: #fff; }
.ci-badge.complete { background: rgba(34,197,94,.2); border-color: rgba(34,197,94,.4); }
.panel-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--c-border); box-shadow: 0 2px 8px rgba(15,23,42,.06); overflow: hidden; }
.panel-head { padding: 18px 24px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #f8fafc; }
.panel-head h3 { margin: 0; font-size: 15px; font-weight: 700; color: var(--c-slate); display: flex; align-items: center; gap: 8px; }
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
.resp-strip { background: #f0fdf4; border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 12px; margin-bottom: 18px; border: 1px solid #bbf7d0; }
.resp-ava { width: 38px; height: 38px; border-radius: 50%; background: var(--c-green-dk); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
.resp-info b { display: block; font-size: 13px; color: #0f172a; }
.resp-info span { font-size: 11px; color: var(--cth-green-text); }
.btn-toolbar { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; font: inherit; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; transition: opacity .2s, transform .1s; }
.btn-toolbar:hover { opacity: .88; transform: translateY(-1px); }
.btn-ghost { background:#f1f5f9; color:#334155; }
.btn-save { background: var(--c-green); color: #fff; }
.dl-pair { display:inline-flex; gap:8px; }
.btn-dl {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    padding: 0 14px;
    border: 0;
    border-radius: 999px;
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 1px 2px rgba(15,23,42,.12);
    transition: transform .12s ease, filter .12s ease;
}
.btn-dl:hover { transform: translateY(-1px); filter: brightness(1.05); }
.btn-dl-word { background: #1d4ed8; }
.btn-dl-pdf { background: #e24b3a; }
.doc-interactive-field:focus {
    background-color: #fff !important;
    border-color: var(--c-green) !important;
    box-shadow: 0 0 0 3px rgba(34,197,94,.2) !important;
}
.phpword-document-container { overflow-x: auto; }
.phpword-document-container > div { overflow-x: hidden; max-width: 850px; }
.phpword-document-container p { margin: 0.28em 0; }
.phpword-document-container img { max-width: 100%; height: auto; max-height: 90px; }
.phpword-document-container table { width: 100% !important; height: auto !important; }
.phpword-document-container textarea.doc-interactive-field { max-width: 100%; box-sizing: border-box; }
.phpword-document-container textarea.js-inline { height: 2em !important; min-height: 2em !important; overflow: hidden !important; vertical-align: baseline; white-space: nowrap; }
.phpword-document-container .doc-optional-clause,
.phpword-document-container .doc-optional-bar,
.phpword-document-container .doc-optional-bar input,
.phpword-document-container .doc-optional-bar label {
    pointer-events: auto;
}
.phpword-document-container .doc-optional-bar label { cursor: pointer; }
</style>
@endsection

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Expediente de {{ $caso->nombre }} (CC: {{ $caso->cedula }})</p>
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

{{-- BARRA OSCURA DINÁMICA --}}
<div class="case-info-bar">
    <div>
        <div class="ci-main" id="ci-worker-name">{{ $caso->nombre }} · C.C. {{ $caso->cedula }}</div>
        <div class="ci-sub" id="ci-worker-sub">{{ \App\Support\Modalidades::etiquetaCaso($caso->modalidad, $caso->cargo) }} · {{ $labelTipo }}</div>
    </div>
    <div class="ci-badges">
        @if($estadoDoc->estaDescargado())
            <span class="ci-badge complete"><i class="fas fa-check-circle"></i> Generada y descargada</span>
        @elseif($estadoDoc->estaGenerado())
            <span class="ci-badge complete"><i class="fas fa-file-signature"></i> Generada, no descargada</span>
        @elseif($estadoDoc->estado === 'en_diligenciamiento' || $estadoDoc->estado === 'completo')
            <span class="ci-badge"><i class="fas fa-pen"></i> Borrador</span>
        @else
            <span class="ci-badge"><i class="fas fa-pen"></i> Pendiente</span>
        @endif
    </div>
</div>

<form action="{{ route('documentos.save', [$caso->id, $tipo]) }}" method="POST" id="edit-form"@if(!$puedeEscribir) data-lock="1"@endif>
    @csrf
    @method('PUT')

    @php
        $me = clone $profileUser;
        $iniciales = collect(preg_split('/\s+/', trim($me->name)))->filter()->take(2)
            ->map(fn($p) => strtoupper(substr($p,0,1)))->implode('');
    @endphp

    <div class="panel-card doc-preview-wrapper" style="margin-bottom: 24px;">
        <div class="panel-head" style="background:#f8fafc;">
            <h3><i class="fas fa-file-contract" style="color:var(--c-green);"></i> Documento Interactivo Oficial</h3>
            <div class="doc-yellow-hint">
                <i class="fas fa-highlighter"></i>
                <div>
                    @if(!empty($bloqueado))
                        <strong>Este proceso está {{ $caso->estado === 'Archivado' ? 'archivado' : 'sancionado' }}.</strong>
                        Solo la coordinadora puede modificarlo.
                    @elseif($yaGenerado)
                        @if($puedeEscribir)
                            <strong>El documento ya se generó.</strong>
                            Guardar edición cambia las zonas amarillas.
                            <small>No es continuar un borrador.</small>
                        @else
                            <strong>El documento ya se generó.</strong>
                            Las zonas amarillas no se pueden cambiar.
                            <small>La edición se otorga aparte y con tiempo.</small>
                        @endif
                    @else
                        <strong>Las zonas amarillas son editables.</strong>
                        Las que no se rellenen no se generan en el documento.
                        <small>Genera y descárgalo en Generar documentos.</small>
                    @endif
                </div>
            </div>
        </div>

        @if(!empty($slot) && \App\Models\CasoDocumentoEstado::slotTieneOpciones($slot))
            <div style="padding:14px 24px; border-bottom:1px solid var(--c-border); display:flex; gap:8px; flex-wrap:wrap; align-items:center; background:#fff;">
                <span style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Usar formato</span>
                @foreach(\App\Models\CasoDocumentoEstado::variantesDe($slot) as $variante)
                    <a href="{{ route('documentos.edit', [$caso->id, $variante]) }}"
                       class="btn-toolbar {{ $variante === $tipo ? 'btn-save' : 'btn-ghost' }}"
                       style="text-decoration:none;">
                        {{ \App\Models\CasoDocumentoEstado::etiqueta($variante) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if(!empty($requiereFirmaGerente))
            <div class="sipd-alert sipd-alert-warning" style="margin:16px 24px 0;">
                <i class="fas fa-print"></i>
                <div>Este formato se llena y queda registrado igual que los demás. Después hay que imprimirlo para firma del gerente y subir el escaneo en <a href="{{ route('abogado.anexos', ['caso' => $caso->id]) }}">Anexos escaneados</a>.</div>
            </div>
        @endif
        
        {{-- Toolbar superior --}}
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #fff;">
            <div class="resp-strip" style="margin-bottom:0; flex:1; min-width: 250px;">
                <div class="resp-ava">{{ $iniciales }}</div>
                <div class="resp-info">
                    <b>{{ $me->name }}</b>
                    <span>{{ $me->cargo ?: ucfirst($me->role) }} · {{ now()->format('d/m/Y') }}</span>
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <label class="btn-toolbar btn-ghost" style="cursor:pointer;">
                    <i class="fas fa-signature"></i> Firma
                    <input type="file" name="firma_digital" accept="image/png, image/jpeg" style="display:none;" onchange="previewFirma(this)">
                </label>
                <a href="{{ route('documentos.hub') }}" class="btn-toolbar btn-ghost" style="text-decoration:none;">
                    <i class="fas fa-arrow-left"></i> Generar documentos
                </a>
                @if(!$yaGenerado && $puedeEscribir && auth()->user()->puede('editar_documentos'))
                <button type="submit" class="btn-toolbar btn-ghost" title="Guarda el avance sin generar el documento">
                    <i class="fas fa-save"></i> Guardar borrador
                </button>
                @endif
                @if($yaGenerado && $puedeEscribir)
                <button type="submit" class="btn-toolbar btn-ghost" title="Guarda cambios en un documento ya generado">
                    <i class="fas fa-save"></i> Guardar edición
                </button>
                @endif
                @if(!$yaGenerado && $puedeEscribir && auth()->user()->puede('generar_documentos'))
                <button type="submit" name="formato" value="generar" class="btn-toolbar btn-save" title="Marca el documento como generado. La descarga queda en Generar documentos.">
                    <i class="fas fa-file-signature"></i> Generar
                </button>
                @endif
            </div>
        </div>

        {{-- Área del documento --}}
        <div class="phpword-document-container" style="background: #e2e8f0; padding: 24px 16px; overflow-x: auto;">
            <div style="background: #ffffff; max-width: 850px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 28px 32px; border-radius: 4px; border: 1px solid #ccc; font-family: 'Arial', sans-serif;">
                {!! $interactiveHtml !!}
            </div>
        </div>
    </div>
</form>

<div class="panel-card" style="margin-top:24px;padding:18px 24px;">
    <h3 style="margin:0 0 10px;font-size:15px;"><i class="fas fa-file-upload" style="color:var(--c-green);"></i> Anexos del expediente</h3>
    @include('partials.anexos-expediente', [
        'anexos' => $caso->anexos,
        'casoId' => $caso->id,
    ])
</div>

<script>
function autosizeTextareas(root) {
    (root || document).querySelectorAll('textarea.js-autosize, textarea[name^="yellow_blocks_"], textarea[name^="yellow_blocks"]').forEach(function(el) {
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

function previewFirma(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.querySelectorAll('.sig-zone').forEach(function(z) {
                z.innerHTML = '<img src="' + e.target.result + '" style="max-height:80px; display:block; margin:0 auto; margin-bottom: 5px;">';
            });
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    autosizeTextareas(document);
    if (typeof window.initSipdOptionalClauses === 'function') {
        window.initSipdOptionalClauses(document);
    }
    var form = document.getElementById('edit-form');
    if (!form || form.getAttribute('data-lock') !== '1') {
        return;
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
    });
    form.querySelectorAll('textarea, input, select').forEach(function (el) {
        if (el.type === 'hidden' || el.name === '_token' || el.name === '_method') {
            return;
        }
        el.readOnly = true;
        el.disabled = el.tagName === 'SELECT';
        el.style.cursor = 'not-allowed';
    });
});
</script>
@endsection
