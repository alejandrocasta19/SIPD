@php
    $lista = $anexos ?? collect();
    $casoId = $casoId ?? null;
@endphp
@if($lista->isEmpty())
    <p class="mb-2">Aún no hay anexos en este expediente.</p>
@else
    @foreach($lista as $anexo)
        <p class="mb-2">
            @if(auth()->user()->puede('descargar_anexos'))
            <a href="{{ route('abogado.anexos.download', $anexo->id) }}">
                <i class="fas fa-paperclip"></i>
                {{ $anexo->nombreVisible() }}
            </a>
            @else
            <span>
                <i class="fas fa-paperclip"></i>
                {{ $anexo->nombreVisible() }}
            </span>
            @endif
            <span class="hub-sub" style="display:inline;margin-left:6px;">
                {{ $anexo->etiquetaLugar() }} · {{ $anexo->etiquetaEstado() }}
                · {{ strtoupper($anexo->extensionVigente()) }} · {{ $anexo->tamanoVigente() }}
                @if($anexo->nombre_original && $anexo->nombre_original !== $anexo->nombreVisible())
                    · {{ $anexo->nombre_original }}
                @endif
            </span>
        </p>
    @endforeach
@endif
@if($casoId)
    <p class="mb-0">
        <a href="{{ route('abogado.anexos', ['caso' => $casoId]) }}">Cargar en Anexos escaneados</a>
    </p>
@endif
