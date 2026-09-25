<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consultar mi caso · SIPD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #22c55e;
            --ink: #0f172a;
            --muted: #64748b;
            --hero: #07111f;
            --purple: #7c3aed;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, system-ui, sans-serif;
            background: #f4f6fa;
            color: var(--ink);
        }
        .hero {
            background: var(--hero);
            color: #fff;
            padding: 28px 20px 36px;
        }
        .hero-inner { max-width: 720px; margin: 0 auto; }
        .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 28px;
        }
        .brand-left { display: flex; align-items: center; gap: 10px; }
        .brand-name { font-weight: 700; letter-spacing: .04em; }
        .brand-sub { font-size: 10px; letter-spacing: .12em; color: #94a3b8; }
        .back { color: #cbd5e1; text-decoration: none; font-size: 14px; }
        .badge {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.12);
            color: #cbd5e1;
            font-size: 11px;
            letter-spacing: .12em;
            font-weight: 600;
            margin-bottom: 14px;
        }
        h1 { margin: 0 0 8px; font-size: 34px; letter-spacing: -.03em; }
        .lead { color: #94a3b8; margin: 0; line-height: 1.6; }
        .wrap { max-width: 720px; margin: -24px auto 60px; padding: 0 20px; }
        .card {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(15,23,42,.06);
            margin-bottom: 16px;
        }
        label { display: block; font-weight: 600; margin-bottom: 8px; }
        input {
            width: 100%;
            height: 48px;
            border: 1px solid #eef2f6;
            border-radius: 14px;
            padding: 0 14px;
            font: inherit;
            background: #f8fafc;
        }
        .actions { display: flex; gap: 10px; margin-top: 16px; }
        .btn {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-purple { background: var(--purple); color: #fff; }
        .error { color: #dc2626; font-size: 13px; margin-top: 6px; }
        .item-top { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
        .code { color: #16a34a; font-weight: 700; }
        .st { font-weight: 600; font-size: 13px; }
        .meta { color: var(--muted); font-size: 14px; line-height: 1.6; }
        .empty { color: var(--muted); }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-inner">
            <div class="brand">
                <div class="brand-left">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8">
                        <path d="M12 3l7 3v6c0 5-3.2 8.4-7 9.5C8.2 20.4 5 17 5 12V6l7-3z"></path>
                    </svg>
                    <div>
                        <div class="brand-name">SIPD</div>
                        <div class="brand-sub">PROCESOS DISCIPLINARIOS</div>
                    </div>
                </div>
                <a class="back" href="{{ route('login') }}">← Volver al acceso</a>
            </div>
            <div class="badge">ACCESO PÚBLICO</div>
            <h1>Consultar mi caso</h1>
            <p class="lead">Ingresa tu cédula para ver el estado de tus procesos disciplinarios.</p>
        </div>
    </div>

    <div class="wrap">
        <div class="card">
            <form method="POST" action="{{ route('consulta.publica.buscar') }}">
                @csrf
                <label for="cedula">Número de cédula</label>
                <input id="cedula" type="text" name="cedula" value="{{ old('cedula', $cedula ?? '') }}" placeholder="Ej. 1075..." required>
                @error('cedula')
                    <div class="error">{{ $message }}</div>
                @enderror
                <div class="actions">
                    <button type="submit" class="btn btn-purple">Consultar</button>
                </div>
            </form>
        </div>

        @if(!empty($consultado))
            @forelse($procesos as $proceso)
                <div class="card">
                    <div class="item-top">
                        <div class="code">PRO-{{ str_pad($proceso->id, 3, '0', STR_PAD_LEFT) }}</div>
                        <div class="st">{{ $proceso->estado }}</div>
                    </div>
                    <div class="meta">
                        <div><strong>{{ $proceso->nombre }}</strong></div>
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
                <div class="card empty">No se encontraron procesos con esa cédula.</div>
            @endforelse
        @endif
    </div>
</body>
</html>
