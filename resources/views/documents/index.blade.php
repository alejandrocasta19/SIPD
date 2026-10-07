@extends('layouts.master')

@php $pageTitle = 'Documentos del caso'; @endphp

@section('styles')
<style>
    .case-doc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:14px; }
    .case-doc-card, .case-doc-files { background:#fff; border:1px solid var(--cth-border); border-radius:14px; padding:18px; }
    .case-doc-card h2, .case-doc-files h2 { margin:0 0 12px; font-size:16px; color:#0f172a; }
    .case-doc-card p { min-height:38px; margin:0 0 14px; color:#64748b; font-size:13px; }
    .case-doc-card a, .case-doc-files a { color:var(--cth-green); font-weight:700; text-decoration:none; }
    .case-doc-card .case-doc-state { display:block; margin-bottom:12px; color:#475569; font-size:12px; font-weight:700; }
    .case-doc-files { margin-top:20px; }
    .case-doc-file { display:flex; justify-content:space-between; gap:12px; padding:12px 0; border-top:1px solid var(--cth-border); }
    .case-doc-file small { display:block; margin-top:3px; color:#64748b; }
</style>
@endsection

@section('page-header')
<div class="proc-head">
    <div>
        <h1>Documentos del expediente</h1>
        <p>{{ $caso->nombre }} · C.C. {{ $caso->cedula }} · PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</p>
    </div>
    <div class="proc-head-side">
        <a class="btn-alt" href="{{ route('documentos.hub') }}"><i class="fas fa-arrow-left"></i> Volver a documentos</a>
    </div>
</div>
@endsection

@section('content')
<section class="case-doc-grid">
    @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $tipos)
        @php
            $tipo = $caso->varianteDelSlot($slot);
            $estado = $estados[$tipo];
        @endphp
        <article class="case-doc-card">
            <h2>{{ \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot] }}</h2>
            <p>{{ \App\Models\CasoDocumentoEstado::etiqueta($tipo) }}</p>
            <span class="case-doc-state">{{ $estado->etiquetaHub() }}</span>
            <a href="{{ route('documentos.edit', [$caso->id, $tipo]) }}">
                <i class="fas fa-file-alt"></i> Abrir documento
            </a>
        </article>
    @endforeach
</section>

<section class="case-doc-files">
    <h2>Anexos del expediente</h2>
    @forelse($caso->anexos as $anexo)
        <div class="case-doc-file">
            <div>
                <strong>{{ $anexo->titulo }}</strong>
                <small>{{ $anexo->nombre_original }} · {{ $anexo->estado }}</small>
            </div>
        </div>
    @empty
        <p>No hay anexos cargados en este expediente.</p>
    @endforelse

    <h2 style="margin-top:20px;">Evidencias del expediente</h2>
    @forelse($caso->evidencias as $evidencia)
        <div class="case-doc-file">
            <div>
                <strong>{{ $evidencia->nombre_original }}</strong>
                @if($evidencia->descripcion)<small>{{ $evidencia->descripcion }}</small>@endif
            </div>
            <a href="{{ route('documentos.evidencias.download', [$caso->id, $evidencia->id]) }}">Descargar</a>
        </div>
    @empty
        <p>No hay evidencias cargadas en este expediente.</p>
    @endforelse
</section>
@endsection
