@php
    $paginador = $paginador ?? null;
    $etiqueta  = $etiqueta  ?? 'registros';
    if ($paginador) {
        $hayDatos = $paginador->total() > 0;
        $pagina   = $paginador->currentPage();
        $ultima   = $paginador->lastPage();
        $desde    = max(1, $pagina - 2);
        $hasta    = min($ultima, $pagina + 2);
    }
@endphp
@if($paginador)
    <div class="sipd-pager">
        @if($hayDatos)
            <span class="sipd-pager-count">
                {{ $paginador->firstItem() }}-{{ $paginador->lastItem() }} de {{ $paginador->total() }} {{ $etiqueta }}
            </span>
        @else
            <span class="sipd-pager-count">0 {{ $etiqueta }}</span>
        @endif

        @if($hayDatos && $paginador->hasPages())
            <nav class="sipd-pager-pages" aria-label="Paginación">
                @if($paginador->onFirstPage())
                    <span class="sipd-page is-off">Anterior</span>
                @else
                    <a class="sipd-page sipd-page-nav" href="{{ $paginador->previousPageUrl() }}">Anterior</a>
                @endif
                @for($n = $desde; $n <= $hasta; $n++)
                    @if($n === $pagina)
                        <span class="sipd-page is-current">{{ $n }}</span>
                    @else
                        <a class="sipd-page sipd-page-nav" href="{{ $paginador->url($n) }}">{{ $n }}</a>
                    @endif
                @endfor
                @if($paginador->hasMorePages())
                    <a class="sipd-page sipd-page-nav" href="{{ $paginador->nextPageUrl() }}">Siguiente</a>
                @else
                    <span class="sipd-page is-off">Siguiente</span>
                @endif
            </nav>
        @endif
    </div>

    <script>
    // Fase bubble (capture=false) → corre DESPUÉS de los listeners capture del master
    // El master guarda la posición actual en click+capture. Nosotros la sobreescribimos
    // con top:0 en click+bubble, siendo la última escritura antes de navegar.
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a.sipd-page-nav');
        if (!a) return;
        if (document.documentElement.scrollHeight <= window.innerHeight * 1.6) return;
        try {
            var dest = new URL(a.href, window.location.href);
            sessionStorage.setItem('sipd_scroll_restore', JSON.stringify({
                path: dest.pathname,
                top: 0,
                anchorTop: null,
                pagerIndex: null,
                sidebarTop: null
            }));
        } catch (e) {}
    }); // sin capture — fase bubble, última en ejecutarse
    </script>
@endif
