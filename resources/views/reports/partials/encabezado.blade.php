@php
    $meta = $meta ?? ($data['meta'] ?? []);
    $logoPath = public_path('images/logo-cootranshuila.png');
    $logoSrc = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $titulo = $titulo ?? ($meta['titulo'] ?? 'Informe de gestión disciplinaria');
    $subtitulo = $subtitulo ?? ($meta['subtitulo'] ?? 'SIPD · Procesos disciplinarios internos');
@endphp
<table class="mast">
    <tr>
        <td class="mast-logo">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="Cootranshuila">
            @endif
        </td>
        <td class="mast-copy">
            <div class="org">Cooperativa de Transportadores del Huila</div>
            <div class="brand">COOTRANSHUILA</div>
            <div class="sys">{{ $meta['sistema'] ?? 'SIPD — Sistema Interno de Procesos Disciplinarios' }}</div>
        </td>
        <td class="mast-meta">
            <strong>Uso interno</strong><br>
            {{ $meta['generado_el'] ?? now()->format('d/m/Y H:i') }}<br>
            {{ $meta['generado_por'] ?? '' }}
        </td>
    </tr>
</table>
<div class="band">{{ $titulo }}</div>
<p class="lead">{{ $subtitulo }}</p>
