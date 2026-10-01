@php
    $paginador = $paginador ?? null;
    $etiqueta = $etiqueta ?? 'registros';
@endphp
@if($paginador && $paginador->total() > 0)
    @php
        $porPagina = (int) $paginador->perPage();
        $esPreset = \App\Support\Paginacion::esPreset($porPagina);
        $pagina = $paginador->currentPage();
        $ultima = $paginador->lastPage();
        $desde = max(1, $pagina - 2);
        $hasta = min($ultima, $pagina + 2);
    @endphp
    <div class="sipd-pager">
        <form class="sipd-pager-size" method="GET" action="{{ url()->current() }}">
            @foreach(request()->except(['page', 'per_page']) as $clave => $valor)
                @if(is_array($valor))
                    @foreach($valor as $item)
                        <input type="hidden" name="{{ $clave }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                @endif
            @endforeach
            <label>Mostrar</label>
            <select class="sipd-pager-choice" aria-label="Registros por página">
                @foreach(\App\Support\Paginacion::OPCIONES as $opcion)
                    <option value="{{ $opcion }}" {{ $porPagina === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                @endforeach
                <option value="custom" {{ $esPreset ? '' : 'selected' }}>Personalizado</option>
            </select>
            <input type="number" name="per_page" class="sipd-pager-custom {{ $esPreset ? 'is-hidden' : '' }}"
                   min="1" max="{{ \App\Support\Paginacion::MAX }}" value="{{ $porPagina }}"
                   aria-label="Cantidad personalizada">
            <button type="submit" class="sipd-pager-go {{ $esPreset ? 'is-hidden' : '' }}">Aplicar</button>
            <span class="sipd-pager-count">
                {{ $paginador->firstItem() }}-{{ $paginador->lastItem() }} de {{ $paginador->total() }} {{ $etiqueta }}
            </span>
        </form>
        @if($paginador->hasPages())
            <nav class="sipd-pager-pages" aria-label="Paginación">
                @if($paginador->onFirstPage())
                    <span class="sipd-page is-off">Anterior</span>
                @else
                    <a class="sipd-page" href="{{ $paginador->previousPageUrl() }}">Anterior</a>
                @endif
                @for($n = $desde; $n <= $hasta; $n++)
                    @if($n === $pagina)
                        <span class="sipd-page is-current">{{ $n }}</span>
                    @else
                        <a class="sipd-page" href="{{ $paginador->url($n) }}">{{ $n }}</a>
                    @endif
                @endfor
                @if($paginador->hasMorePages())
                    <a class="sipd-page" href="{{ $paginador->nextPageUrl() }}">Siguiente</a>
                @else
                    <span class="sipd-page is-off">Siguiente</span>
                @endif
            </nav>
        @endif
    </div>
@endif
