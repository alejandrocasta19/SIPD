@if($me->puede('ver_documentos'))
    <a href="{{ route('documentos.hub') }}"
       class="{{ request()->routeIs('documentos.*') ? 'active' : '' }}"
       title="Nuevo proceso solo deja un borrador. Aquí se genera y se descarga.">
        <i class="fas fa-file-signature"></i> Generar documentos
    </a>
    @if(request()->routeIs('documentos.*') && request()->route('id'))
        @php
            $docCasoId = request()->route('id');
            $docCaso = \App\Models\ProcesoDisciplinario::find($docCasoId);
            $tipoActual = request()->route('tipo');
        @endphp
        @foreach(\App\Models\CasoDocumentoEstado::SLOTS as $slot => $variantes)
            @php $tipoSlot = $docCaso ? $docCaso->varianteDelSlot($slot) : $variantes[0]; @endphp
            <a href="{{ route('documentos.edit', [$docCasoId, $tipoSlot]) }}"
               class="{{ request()->routeIs('documentos.edit') && in_array($tipoActual, $variantes, true) ? 'active' : '' }}"
               style="padding-left:28px;font-size:13px;">
                <i class="fas {{ \App\Models\CasoDocumentoEstado::SLOT_ICONS[$slot] }}"></i>
                {{ \App\Models\CasoDocumentoEstado::SLOT_LABELS[$slot] }}
            </a>
        @endforeach
    @endif
@endif
