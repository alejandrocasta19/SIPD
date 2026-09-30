<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consultar mi caso · SIPD Cootranshuila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=2">
</head>
<body>
    <div class="consulta-hero">
        <div class="consulta-inner">
            <div class="brand" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:28px;">
                <a class="guest-brand" href="{{ route('login') }}">
                    <div class="guest-brand-mark">C</div>
                    <div>
                        <strong>SIPD</strong>
                        <small>Cootranshuila</small>
                    </div>
                </a>
                <a class="back" href="{{ route('login') }}" style="color:#e8f5ee;text-decoration:none;font-size:14px;">← Volver al acceso</a>
            </div>
            <div class="guest-badge">ACCESO PÚBLICO</div>
            <h1 style="margin:0 0 8px;font-size:34px;letter-spacing:-.03em;">Consultar mi caso</h1>
            <p style="color:rgba(255,255,255,.78);margin:0;line-height:1.6;">Ingresa tu cédula para ver el estado de tus procesos disciplinarios.</p>
        </div>
    </div>

    <div class="consulta-wrap">
        <div class="consulta-card">
            <form method="POST" action="{{ route('consulta.publica.buscar') }}">
                @csrf
                <div class="guest-field">
                    <label for="cedula">Número de cédula</label>
                    <input id="cedula" type="text" name="cedula" value="{{ old('cedula', $cedula ?? '') }}" placeholder="Ej. 1075..." required>
                    @error('cedula')
                        <div class="guest-error">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn-submit" style="width:auto;padding:0 22px;height:44px;">Consultar</button>
            </form>
        </div>

        @if(!empty($consultado))
            @forelse($procesos as $proceso)
                <div class="consulta-card">
                    <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:8px;">
                        <div style="color:var(--cth-green);font-weight:700;">PRO-{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</div>
                        <div style="font-weight:600;font-size:13px;">{{ $proceso->estado }}</div>
                    </div>
                    <div style="color:var(--cth-muted);font-size:14px;line-height:1.6;">
                        <div><strong style="color:var(--cth-ink);">{{ $proceso->nombre }}</strong></div>
                        <div>Tipo de falta: {{ $proceso->tipo_falta ?: '—' }}</div>
                        <div>Fecha: {{ $proceso->fecha_falta ?: '—' }}</div>
                        @if($proceso->descripcion_falta)
                            <div>{{ $proceso->descripcion_falta }}</div>
                        @endif
                        @if($proceso->decision_final)
                            <div>Decisión: {{ $proceso->decision_final }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="consulta-card" style="color:var(--cth-muted);">No se encontraron procesos con esa cédula.</div>
            @endforelse
        @endif
    </div>
</body>
</html>
