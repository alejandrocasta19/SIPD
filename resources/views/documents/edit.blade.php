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
.panel-head { padding: 18px 24px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #f8fafc; }
.panel-head h3 { margin: 0; font-size: 15px; font-weight: 700; color: var(--c-slate); display: flex; align-items: center; gap: 8px; }
.resp-strip { background: #f0fdf4; border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 12px; margin-bottom: 18px; border: 1px solid #bbf7d0; }
.resp-ava { width: 38px; height: 38px; border-radius: 50%; background: var(--c-green-dk); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
.resp-info b { display: block; font-size: 13px; color: #0f172a; }
.resp-info span { font-size: 11px; color: var(--cth-green-text); }
.btn-toolbar { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 10px; font: inherit; font-size: 13px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; transition: opacity .2s, transform .1s; }
.btn-toolbar:hover { opacity: .88; transform: translateY(-1px); }
.doc-interactive-field:focus {
    background-color: #fff !important;
    border-color: var(--c-green) !important;
    box-shadow: 0 0 0 3px rgba(34,197,94,.2) !important;
}
</style>
@endsection

@section('page-header')
<div class="sipd-header">
    <div class="sipd-header-title">
        <h1 class="sipd-page-title" style="margin:0;">{{ $pageTitle }}</h1>
        <p style="color:#64748b;font-size:13px;margin:4px 0 0;">
            Expediente de {{ $caso->nombre }} (CC: {{ $caso->cedula }})
        </p>
    </div>
</div>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
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

{{-- BARRA OSCURA DINÁMICA --}}
<div class="case-info-bar">
    <div>
        <div class="ci-main" id="ci-worker-name">{{ $caso->nombre }} · C.C. {{ $caso->cedula }}</div>
        <div class="ci-sub" id="ci-worker-sub">{{ $caso->modalidad ?: '—' }} · {{ $labelTipo }}</div>
    </div>
    <div class="ci-badges">
        @if($estadoDoc->estado === 'completo')
            <span class="ci-badge complete"><i class="fas fa-check-circle"></i> Completo</span>
        @else
            <span class="ci-badge"><i class="fas fa-pen"></i> En diligenciamiento</span>
        @endif
    </div>
</div>

<form action="{{ route('documentos.save', [$caso->id, $tipo]) }}" method="POST" id="edit-form">
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
            <div style="font-size:12px; color:var(--c-muted);">Las zonas amarillas son editables</div>
        </div>
        
        {{-- Toolbar superior --}}
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
                    <input type="file" name="firma_digital" accept="image/png, image/jpeg" style="display:none;" onchange="previewFirma(this)">
                </label>
                <a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="btn-toolbar btn-back" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver al Caso</a>
                <button type="submit" class="btn-toolbar btn-save" style="background: var(--c-green); color: #fff;">
                    <i class="fas fa-check-circle"></i> Guardar Cambios
                </button>
                @if($estadoDoc->estado === 'completo')
                    <a href="{{ route('documentos.download', [$caso->id, $tipo, Str::slug($labelTipo)]) }}" class="btn-toolbar btn-dl-docx" style="background: #1d4ed8; color: #fff; text-decoration:none;">
                        <i class="fas fa-file-word"></i> Descargar DOCX Oficial
                    </a>
                @endif
            </div>
        </div>

        {{-- Área del documento --}}
        <div class="phpword-document-container" style="background: #e2e8f0; padding: 40px 20px; overflow-x: auto;">
            <div style="background: #ffffff; max-width: 850px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 50px; border-radius: 4px; border: 1px solid #ccc; font-family: 'Arial', sans-serif;">
                {!! $interactiveHtml !!}
            </div>
        </div>
    </div>
</form>

<script>
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
</script>
@endsection
