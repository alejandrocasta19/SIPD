@extends('layouts.master')

@section('styles')
<style>
/* ─── Variables ─────────────────────────── */
:root {
    --radius: 18px;
}

/* ─── Layout ─────────────────────────────── */
.doc-page { max-width: 1100px; margin: 0 auto; }

/* ─── Case header card ───────────────────── */
.case-hero {
    background: linear-gradient(135deg, var(--cth-sidebar) 0%, var(--cth-green) 100%);
    border-radius: var(--radius);
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.case-hero-info h2 {
    margin: 0 0 4px;
    font-size: 22px;
    font-weight: 700;
}

.case-hero-info p {
    margin: 0;
    color: rgba(255,255,255,.65);
    font-size: 14px;
}

.case-hero-badge {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 999px;
    padding: 6px 16px;
    font-size: 13px;
    color: #fff;
}

/* ─── Document cards grid ───────────────── */
.doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.doc-card {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid var(--c-border);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,.06);
    display: flex;
    flex-direction: column;
    transition: box-shadow .2s, transform .2s;
}

.doc-card:hover {
    box-shadow: 0 8px 24px rgba(15,23,42,.12);
    transform: translateY(-2px);
}

.doc-card-header {
    padding: 20px 24px 14px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.doc-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 20px;
    flex-shrink: 0;
}

.doc-icon.disciplinario,
.doc-icon.apertura { background: #eff6ff; color: #1d4ed8; }
.doc-icon.comprobacion  { background: #fff7ed; color: #c2410c; }
.doc-icon.acta          { background: #fdf4ff; color: #7c3aed; }
.doc-icon.resolucion,
.doc-icon.sancion,
.doc-icon.llamado,
.doc-icon.terminacion { background: #fef2f2; color: #b91c1c; }
.doc-icon.archivo { background: #f1f5f9; color: #475569; }

.variant-pills { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
.variant-pills a, .variant-pills button {
    border: 1px solid var(--cth-border); background:#f8fafc; color:#334155;
    border-radius:999px; padding:4px 10px; font-size:11px; font-weight:700;
    cursor:pointer; text-decoration:none;
}
.variant-pills a.active, .variant-pills button.active {
    background:var(--c-green); color:#fff; border-color:transparent;
}

.doc-card-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--c-slate);
    margin: 0 0 4px;
    line-height: 1.3;
}

.doc-card-sub { font-size: 12px; color: var(--c-muted); }

/* ─── Estado badge ───────────────────────── */
.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    border: 1.5px solid transparent;
}

.estado-badge.no_iniciado       { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
.estado-badge.en_diligenciamiento { background: #fefce8; color: #854d0e; border-color: #fef08a; }
.estado-badge.completo          { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.estado-badge.generado          { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.estado-badge.db-pendiente { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
.estado-badge.db-en_diligenciamiento { background: #fefce8; color: #854d0e; border-color: #fef08a; }
.estado-badge.db-generado { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.estado-badge.db-descargado { background: #f0fdf4; color: var(--cth-green-text); border-color: #bbf7d0; }

.doc-card-body {
    padding: 0 24px 18px;
    flex: 1;
}

.doc-meta {
    font-size: 12px;
    color: var(--c-muted);
    margin-bottom: 6px;
}

.doc-card-footer {
    border-top: 1px solid var(--c-border);
    padding: 14px 24px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-doc {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: opacity .2s;
}

.btn-doc:hover { opacity: .85; }
.btn-edit { background: var(--c-green); color: #fff; }
.btn-preview { background: #f1f5f9; color: #334155; }
.btn-download { background: #1d4ed8; color: #fff; }
.btn-download-pdf { background: #e24b3a; color: #fff; }

/* ─── Evidencias ─────────────────────────── */
.ev-section {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid var(--c-border);
    padding: 24px 28px;
    margin-bottom: 24px;
}

.ev-section h3 {
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ev-list { display: grid; gap: 10px; }

.ev-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--c-border);
    border-radius: 12px;
    padding: 12px 16px;
    gap: 12px;
}

.ev-item-info { display: flex; align-items: center; gap: 12px; min-width: 0; }

.ev-thumb {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid var(--c-border);
}

.ev-icon-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f1f5f9;
    display: grid;
    place-items: center;
    color: #94a3b8;
    font-size: 18px;
    flex-shrink: 0;
}

.ev-name {
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ev-meta { font-size: 12px; color: var(--c-muted); margin-top: 2px; }

.ev-actions { display: flex; gap: 6px; flex-shrink: 0; }

.btn-ev {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-ev-download { background: #eff6ff; color: #1d4ed8; }
.btn-ev-delete   { background: #fef2f2; color: #dc2626; }

.ev-empty {
    text-align: center;
    padding: 32px;
    color: var(--c-muted);
    font-size: 14px;
}

/* ─── Upload form ────────────────────────── */
.ev-upload {
    border: 2px dashed var(--c-border);
    border-radius: 12px;
    padding: 20px;
    margin-top: 16px;
}

.ev-upload label {
    font-size: 13px;
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    color: #334155;
}

.ev-upload input[type="file"],
.ev-upload input[type="text"],
.ev-upload textarea {
    width: 100%;
    border: 1px solid var(--c-border);
    border-radius: 10px;
    padding: 9px 12px;
    font: inherit;
    font-size: 13px;
    margin-bottom: 12px;
}

.btn-ev-upload {
    background: var(--c-green);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

/* ─── Responsable ────────────────────────── */
.responsable-card {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.resp-ava {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--c-green-dk);
    color: #fff;
    display: grid;
    place-items: center;
    font-weight: 700;
    flex-shrink: 0;
}

.resp-info b   { display: block; font-size: 14px; color: #0f172a; }
.resp-info span { font-size: 12px; color: var(--cth-green-text); }

/* Responsive */
@media (max-width: 700px) {
    .case-hero { padding: 20px; }
    .doc-grid  { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('page-header')
@endsection

@section('content')
<div class="doc-page">

    {{-- ALERTS --}}
    @if($errors->any())
        <div class="sipd-alert sipd-alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        </div>
    @endif

    {{-- CASE HERO --}}
    <div class="case-hero">
        <div class="case-hero-info">
            <h2><i class="fas fa-folder-open" style="color:var(--c-green);margin-right:10px;"></i>
                Caso {{ $caso->numeroExpediente() }}</h2>
            <p>
                <strong>Trabajador:</strong> {{ $caso->nombre }}
                @if($caso->cedula) · C.C. {{ $caso->cedula }}@endif
                @if($caso->modalidad) · {{ $caso->modalidad }}@endif
            </p>
        </div>
        <div>
            <span class="case-hero-badge">{{ ucfirst($caso->estado) }}</span>
        </div>
    </div>

    {{-- RESPONSABLE --}}
    @php
        $me = auth()->user();
        $iniciales = collect(preg_split('/\s+/', trim($me->name)))->filter()->take(2)
            ->map(fn($p) => strtoupper(substr($p,0,1)))->implode('');
    @endphp
    <div class="responsable-card">
        <div class="resp-ava">{{ $iniciales }}</div>
        <div class="resp-info">
            <b>{{ $me->name }}</b>
            <span>{{ $me->cargo ?: ucfirst($me->role) }}
                @if($me->cedula) · C.C. {{ $me->cedula }}@endif
                · {{ now()->format('d/m/Y') }}
            </span>
        </div>
    </div>

    {{-- DOCUMENTOS DEL CASO --}}
    <h2 style="font-size:18px;font-weight:700;margin-bottom:16px;">
        <i class="fas fa-file-signature" style="color:var(--c-green);margin-right:8px;"></i>
        Documentos Oficiales
    </h2>

    <div class="doc-grid">
        @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
            @php
                $tipoSlot = $caso->varianteDelSlot($slot);
                $est = $estados[$tipoSlot] ?? $caso->estadoDocumento($tipoSlot);
            @endphp
            <div class="doc-card">
                <div class="doc-card-header">
                    <div class="doc-icon {{ $slot }}"><i class="fas {{ \App\Models\CasoDocumentoEstado::SLOT_ICONS[$slot] }}"></i></div>
                    <div>
                        <div class="doc-card-title">{{ \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot] }}</div>
                        <div class="doc-card-sub">{{ \App\Models\CasoDocumentoEstado::etiqueta($tipoSlot) }}</div>
                    </div>
                </div>
                <div class="doc-card-body">
                    <span class="estado-badge {{ $est->claseHub() }}">
                        <i class="fas fa-circle" style="font-size:7px;"></i>
                        {{ $est->etiquetaHub() }}
                    </span>
                    @if($est->generado_en)
                        <div class="doc-meta" style="margin-top:8px;">Generado: {{ $est->generado_en->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($est->descargado_en)
                        <div class="doc-meta">Descargado: {{ $est->descargado_en->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($tipoSlot === 'terminacion')
                        <div class="doc-meta" style="margin-top:10px;">Se imprime para firma del gerente y el escaneo se carga en Anexos escaneados.</div>
                    @endif
                    @php $anexosSlot = $caso->anexos->filter(fn ($anexo) => $anexo->slot() === $slot); @endphp
                    @if($anexosSlot->isNotEmpty())
                        <div class="doc-meta" style="margin-top:10px;">
                            @foreach($anexosSlot as $anexo)
                                <div style="margin-top:4px;">
                                    @if(auth()->user()->puede('descargar_anexos'))
                                        <a href="{{ route('abogado.anexos.download', $anexo->id) }}">{{ $anexo->nombreVisible() }}</a>
                                    @else
                                        {{ $anexo->nombreVisible() }}
                                    @endif
                                    @if($anexo->nombre_original && $anexo->nombre_original !== $anexo->nombreVisible())
                                        <span> · {{ $anexo->nombre_original }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="doc-card-footer">
                    <a href="{{ route('documentos.edit', [$caso->id, $tipoSlot]) }}" class="btn-doc btn-edit">
                        <i class="fas fa-edit"></i> Diligenciar
                    </a>
                    @if(auth()->user()->puede('descargar_documentos'))
                    <a href="{{ route('documentos.download', [$caso->id, $tipoSlot]) }}" class="btn-doc btn-download">
                        <i class="fas fa-file-word"></i> Word
                    </a>
                    <a href="{{ route('documentos.download', [$caso->id, $tipoSlot]) }}?format=pdf" class="btn-doc btn-download-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- EVIDENCIAS --}}
    <div class="ev-section">
        <h3><i class="fas fa-paperclip" style="color:var(--c-green);"></i> Evidencias del Caso</h3>

        @if($caso->evidencias->isEmpty())
            <div class="ev-empty">
                <i class="fas fa-inbox" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                No hay evidencias adjuntas.
            </div>
        @else
            <div class="ev-list">
                @foreach($caso->evidencias as $ev)
                    <div class="ev-item">
                        <div class="ev-item-info">
                            @if($ev->esImagen())
                                <img src="{{ route('documentos.evidencias.download', [$caso->id, $ev->id]) }}"
                                     class="ev-thumb" alt="{{ $ev->nombre_original }}">
                            @else
                                <div class="ev-icon-placeholder"><i class="fas fa-file-pdf"></i></div>
                            @endif
                            <div>
                                <div class="ev-name">{{ $ev->nombre_original }}</div>
                                <div class="ev-meta">
                                    {{ strtoupper($ev->extension) }} · {{ $ev->tamanoLegible() }}
                                    @if($ev->descripcion) · {{ $ev->descripcion }}@endif
                                    <br>Cargada {{ $ev->created_at->format('d/m/Y') }} por {{ $ev->user->name ?? '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="ev-actions">
                            <a href="{{ route('documentos.evidencias.download', [$caso->id, $ev->id]) }}"
                               class="btn-ev btn-ev-download">
                                <i class="fas fa-download"></i> Descargar
                            </a>
                            @if(auth()->user()->esCoordinadora() || $ev->user_id === auth()->id())
                                <form method="POST"
                                      action="{{ route('documentos.evidencias.destroy', [$caso->id, $ev->id]) }}"
                                      data-confirm="La evidencia se eliminará del expediente."
                                      data-confirm-title="Eliminar evidencia"
                                      data-confirm-ok="Eliminar"
                                      data-confirm-danger="1"
                                      data-confirm-icon="warning">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-ev btn-ev-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- UPLOAD --}}
        <div class="ev-upload">
            <form action="{{ route('documentos.evidencias.store', $caso->id) }}"
                  method="POST" enctype="multipart/form-data">
                @csrf
                <label for="ev-file">Agregar evidencia (PDF, JPG, JPEG, PNG · máx. 10 MB)</label>
                <input type="file" id="ev-file" name="archivo"
                       accept=".pdf,.jpg,.jpeg,.png" required>
                <label for="ev-desc">Descripción (opcional)</label>
                <input type="text" id="ev-desc" name="descripcion"
                       placeholder="Ej: Informe entregado por supervisor" maxlength="500">
                <button type="submit" class="btn-ev-upload">
                    <i class="fas fa-upload"></i> Cargar evidencia
                </button>
            </form>
        </div>
    </div>

    <div class="ev-section">
        <h3><i class="fas fa-file-upload" style="color:var(--c-green);"></i> Anexos del expediente</h3>
        <p class="ev-meta" style="margin:-8px 0 16px;">Escaneo firmado por gerencia y documentos de un caso anterior. Se cargan en Anexos escaneados.</p>

        @include('partials.anexos-expediente', [
            'anexos' => $caso->anexos,
            'casoId' => $caso->id,
        ])
    </div>

    {{-- LINK VOLVER --}}
    <a href="{{ route('abogado.detalleproceso', $caso->id) }}"
       style="color:var(--c-muted);font-size:13px;text-decoration:none;">
        <i class="fas fa-arrow-left"></i> Volver al detalle del caso
    </a>

</div>
@endsection
